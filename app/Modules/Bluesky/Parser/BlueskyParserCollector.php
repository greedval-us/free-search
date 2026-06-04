<?php

namespace App\Modules\Bluesky\Parser;

use App\Modules\Bluesky\Actions\Request\LoadActorFollowersAction;
use App\Modules\Bluesky\Actions\Request\LoadActorFollowsAction;
use App\Modules\Bluesky\Actions\Request\LoadAuthorFeedAction;
use App\Modules\Bluesky\Actions\Request\LoadPostLikesAction;
use App\Modules\Bluesky\Actions\Request\LoadPostRepostsAction;
use App\Modules\Bluesky\Actions\Request\LoadPostThreadAction;
use App\Modules\Bluesky\DTO\Parser\BlueskyParserCollectedDataDTO;
use App\Modules\Bluesky\DTO\Parser\BlueskyParserStateDTO;
use App\Modules\Bluesky\Enums\BlueskyParserInteractionKind;
use App\Modules\Bluesky\Enums\BlueskyParserStage;
use App\Modules\Bluesky\Support\BlueskyActorResolver;

final class BlueskyParserCollector
{
    private const FEED_LIMIT = 50;
    private const GRAPH_LIMIT = 100;
    private const INTERACTION_LIMIT = 100;
    private const THREAD_DEPTH = 6;

    public function __construct(
        private readonly BlueskyActorResolver $actorResolver,
        private readonly LoadAuthorFeedAction $loadAuthorFeedAction,
        private readonly LoadActorFollowersAction $loadActorFollowersAction,
        private readonly LoadActorFollowsAction $loadActorFollowsAction,
        private readonly LoadPostLikesAction $loadPostLikesAction,
        private readonly LoadPostRepostsAction $loadPostRepostsAction,
        private readonly LoadPostThreadAction $loadPostThreadAction,
        private readonly BlueskyParserSnapshotBuilder $snapshotBuilder,
    ) {
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    public function advance(array $run): array
    {
        $state = BlueskyParserStateDTO::fromArray($run);

        if (! $state->isRunning()) {
            return $state->toArray();
        }

        return match ($state->stage()) {
            BlueskyParserStage::Profile => $this->advanceProfile($state),
            BlueskyParserStage::Feed => $this->advanceFeed($state),
            BlueskyParserStage::Followers => $this->advanceFollowers($state),
            BlueskyParserStage::Follows => $this->advanceFollows($state),
            BlueskyParserStage::Interactions => $this->advanceInteractions($state),
            BlueskyParserStage::Finishing => $this->finish($state),
            default => $state->toArray(),
        };
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    public function buildResultSnapshot(array $run): array
    {
        $state = BlueskyParserStateDTO::fromArray($run);

        return $this->snapshotBuilder->build($state->actorQuery(), $state->data())->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function advanceProfile(BlueskyParserStateDTO $state): array
    {
        $profile = $this->actorResolver->resolve($state->actorQuery());
        $actorId = (string) ($profile['did'] ?? $profile['handle'] ?? '');

        if ($actorId === '') {
            $state->fail(
                message: __('bluesky.parser.errors.actor_not_found'),
                stage: BlueskyParserStage::Failed,
            );

            return $state->toArray();
        }

        $state->data()->setProfile($profile);
        $state->setStage(BlueskyParserStage::Feed);
        $state->setProgress(5);

        return $state->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function advanceFeed(BlueskyParserStateDTO $state): array
    {
        $data = $state->data();
        $cursor = $state->cursor();
        $actor = $data->actorIdentifier();

        $feed = $this->loadAuthorFeedAction->handle(
            actor: $actor,
            limit: self::FEED_LIMIT,
            cursor: $cursor->feedCursor(),
            filter: 'posts_with_replies',
        );

        $expectedDid = $data->profileDid();

        foreach ($feed->items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $authorDid = (string) ($item['author']['did'] ?? '');
            if ($expectedDid !== '' && $authorDid !== '' && $authorDid !== $expectedDid) {
                continue;
            }

            $uri = (string) ($item['uri'] ?? $item['id'] ?? '');

            if ($uri === '') {
                continue;
            }

            $data->recordFeedItem($item);
        }

        $nextCursor = $feed->pagination['nextCursor'] ?? null;
        if (is_string($nextCursor) && $nextCursor !== '') {
            $cursor->setFeedCursor($nextCursor);
            $state->setProgress(min(40, 5 + ($cursor->incrementFeedPage() * 4)));
        } else {
            $cursor->setFeedCursor(null);
            $state->setStage(BlueskyParserStage::Followers);
            $state->setProgress(45);
        }

        return $state->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function advanceFollowers(BlueskyParserStateDTO $state): array
    {
        $data = $state->data();
        $cursor = $state->cursor();
        $actor = $data->actorIdentifier();

        $followers = $this->loadActorFollowersAction->handle(
            actor: $actor,
            limit: self::GRAPH_LIMIT,
            cursor: $cursor->followersCursor(),
        );

        foreach ($followers->items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $data->recordFollower($item);
        }

        $nextCursor = $followers->pagination['nextCursor'] ?? null;
        if (is_string($nextCursor) && $nextCursor !== '') {
            $cursor->setFollowersCursor($nextCursor);
            $state->setProgress(min(55, 45 + ($cursor->incrementFollowersPage() * 2)));
        } else {
            $cursor->setFollowersCursor(null);
            $state->setStage(BlueskyParserStage::Follows);
            $state->setProgress(60);
        }

        return $state->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function advanceFollows(BlueskyParserStateDTO $state): array
    {
        $data = $state->data();
        $cursor = $state->cursor();
        $actor = $data->actorIdentifier();

        $follows = $this->loadActorFollowsAction->handle(
            actor: $actor,
            limit: self::GRAPH_LIMIT,
            cursor: $cursor->followsCursor(),
        );

        foreach ($follows->items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $data->recordFollow($item);
        }

        $nextCursor = $follows->pagination['nextCursor'] ?? null;
        if (is_string($nextCursor) && $nextCursor !== '') {
            $cursor->setFollowsCursor($nextCursor);
            $state->setProgress(min(70, 60 + ($cursor->incrementFollowsPage() * 2)));
        } else {
            $cursor->setFollowsCursor(null);
            $cursor->resetInteraction();
            $state->setStage(BlueskyParserStage::Interactions);
            $state->setProgress(75);
        }

        return $state->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function advanceInteractions(BlueskyParserStateDTO $state): array
    {
        $data = $state->data();
        $cursor = $state->cursor();
        $authoredItems = $data->authoredItems();

        $postIndex = $cursor->interactionPostIndex();
        if ($postIndex >= count($authoredItems)) {
            $state->setStage(BlueskyParserStage::Finishing);
            $state->setProgress(95);

            return $state->toArray();
        }

        $post = is_array($authoredItems[$postIndex] ?? null) ? $authoredItems[$postIndex] : [];
        $postUri = (string) ($post['uri'] ?? '');
        $postCid = (string) ($post['cid'] ?? '');
        $kind = $cursor->interactionKind();

        if ($postUri === '') {
            return $this->moveToNextInteractionPost($state, $postIndex);
        }

        return match ($kind) {
            BlueskyParserInteractionKind::Likes => $this->collectPostLikes($state, $postUri, $postCid, $postIndex),
            BlueskyParserInteractionKind::Reposts => $this->collectPostReposts($state, $postUri, $postCid, $postIndex),
            BlueskyParserInteractionKind::Replies => $this->collectPostReplies($state, $postUri, $postIndex),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function collectPostLikes(
        BlueskyParserStateDTO $state,
        string $postUri,
        string $postCid,
        int $postIndex,
    ): array {
        $data = $state->data();
        $cursor = $state->cursor();

        $likes = $this->loadPostLikesAction->handle(
            uri: $postUri,
            cid: $postCid !== '' ? $postCid : null,
            limit: self::INTERACTION_LIMIT,
            cursor: $cursor->interactionCursor(),
        );

        foreach ($likes->items as $item) {
            if (is_array($item)) {
                $data->recordReaction($postUri, $postCid, BlueskyParserInteractionKind::Likes, $item);
            }
        }

        $nextCursor = $likes->pagination['nextCursor'] ?? null;
        if (is_string($nextCursor) && $nextCursor !== '') {
            $cursor->setInteractionCursor($nextCursor);
        } else {
            $cursor->setInteractionKind(BlueskyParserInteractionKind::Reposts);
            $cursor->setInteractionCursor(null);
        }

        $state->setProgress($this->interactionProgress($data, $postIndex));

        return $state->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function collectPostReposts(
        BlueskyParserStateDTO $state,
        string $postUri,
        string $postCid,
        int $postIndex,
    ): array {
        $data = $state->data();
        $cursor = $state->cursor();

        $reposts = $this->loadPostRepostsAction->handle(
            uri: $postUri,
            limit: self::INTERACTION_LIMIT,
            cursor: $cursor->interactionCursor(),
        );

        foreach ($reposts->items as $item) {
            if (is_array($item)) {
                $data->recordReaction($postUri, $postCid, BlueskyParserInteractionKind::Reposts, $item);
            }
        }

        $nextCursor = $reposts->pagination['nextCursor'] ?? null;
        if (is_string($nextCursor) && $nextCursor !== '') {
            $cursor->setInteractionCursor($nextCursor);
        } else {
            $cursor->setInteractionKind(BlueskyParserInteractionKind::Replies);
            $cursor->setInteractionCursor(null);
        }

        $state->setProgress($this->interactionProgress($data, $postIndex));

        return $state->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function collectPostReplies(
        BlueskyParserStateDTO $state,
        string $postUri,
        int $postIndex,
    ): array {
        $thread = $this->loadPostThreadAction->handle($postUri, self::THREAD_DEPTH, 0);
        $state->data()->recordReceivedReplies($postUri, $this->flattenReplies($thread->replies));

        return $this->moveToNextInteractionPost($state, $postIndex);
    }

    /**
     * @return array<string, mixed>
     */
    private function moveToNextInteractionPost(BlueskyParserStateDTO $state, int $postIndex): array
    {
        $data = $state->data();
        $cursor = $state->cursor();

        $cursor->resetInteraction($postIndex + 1);
        $state->setProgress($this->interactionProgress($data, $postIndex + 1));

        if (($postIndex + 1) >= count($data->authoredItems())) {
            $state->setStage(BlueskyParserStage::Finishing);
            $state->setProgress(95);
        }

        return $state->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function finish(BlueskyParserStateDTO $state): array
    {
        $state->complete(
            result: $this->snapshotBuilder->build($state->actorQuery(), $state->data())->toArray(),
            stage: BlueskyParserStage::Completed,
        );

        return $state->toArray();
    }

    /**
     * @param array<int, array<string, mixed>> $nodes
     * @return array<int, array<string, mixed>>
     */
    private function flattenReplies(array $nodes): array
    {
        $items = [];

        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            $children = is_array($node['replies'] ?? null) ? $node['replies'] : [];
            $item = $node;
            $item['replies'] = [];
            $items[] = $item;

            foreach ($this->flattenReplies($children) as $child) {
                $items[] = $child;
            }
        }

        return $items;
    }

    /**
     */
    private function interactionProgress(BlueskyParserCollectedDataDTO $data, int $processedPosts): int
    {
        $total = count($data->authoredItems());

        if ($total <= 0) {
            return 95;
        }

        return min(99, 75 + (int) floor((min($processedPosts, $total) / $total) * 24));
    }
}
