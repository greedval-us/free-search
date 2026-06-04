<?php

namespace App\Modules\Bluesky\Parser;

use App\Modules\Bluesky\Actions\Request\LoadActorFollowersAction;
use App\Modules\Bluesky\Actions\Request\LoadActorFollowsAction;
use App\Modules\Bluesky\Actions\Request\LoadAuthorFeedAction;
use App\Modules\Bluesky\Actions\Request\LoadPostLikesAction;
use App\Modules\Bluesky\Actions\Request\LoadPostRepostsAction;
use App\Modules\Bluesky\Actions\Request\LoadPostThreadAction;
use App\Modules\Bluesky\DTO\Result\BlueskyThreadResultDTO;
use App\Modules\Bluesky\Parser\Enums\BlueskyParserStage;
use App\Modules\Bluesky\Support\BlueskyActorResolver;
use App\Modules\ParserSupport\ParserRunLifecycleManager;

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
        private readonly ParserRunLifecycleManager $lifecycleManager,
    ) {
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    public function advance(array $run): array
    {
        if (! $this->lifecycleManager->isRunning($run)) {
            return $run;
        }

        $stage = BlueskyParserStage::tryFrom((string) ($run['stage'] ?? ''))
            ?? BlueskyParserStage::Profile;

        return match ($stage) {
            BlueskyParserStage::Profile => $this->advanceProfile($run),
            BlueskyParserStage::Feed => $this->advanceFeed($run),
            BlueskyParserStage::Followers => $this->advanceFollowers($run),
            BlueskyParserStage::Follows => $this->advanceFollows($run),
            BlueskyParserStage::Interactions => $this->advanceInteractions($run),
            BlueskyParserStage::Finishing => $this->finish($run),
            default => $run,
        };
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    public function buildResultSnapshot(array $run): array
    {
        $context = is_array($run['context'] ?? null) ? $run['context'] : [];
        $data = is_array($run['data'] ?? null) ? $run['data'] : [];

        $profile = is_array($data['profile'] ?? null) ? $data['profile'] : null;
        $postsIndex = array_values(is_array($data['postsIndex'] ?? null) ? $data['postsIndex'] : []);
        $authoredRepliesIndex = array_values(is_array($data['authoredRepliesIndex'] ?? null) ? $data['authoredRepliesIndex'] : []);
        $receivedRepliesIndex = array_values(is_array($data['receivedRepliesIndex'] ?? null) ? $data['receivedRepliesIndex'] : []);
        $followersIndex = array_values(is_array($data['followersIndex'] ?? null) ? $data['followersIndex'] : []);
        $followsIndex = array_values(is_array($data['followsIndex'] ?? null) ? $data['followsIndex'] : []);
        $reactionsIndex = array_values(is_array($data['reactionsIndex'] ?? null) ? $data['reactionsIndex'] : []);

        return [
            'actor' => (string) ($context['actor'] ?? ''),
            'resolvedActor' => $profile,
            'postsCount' => count($postsIndex),
            'authoredRepliesCount' => count($authoredRepliesIndex),
            'receivedRepliesCount' => count($receivedRepliesIndex),
            'followersCount' => count($followersIndex),
            'followsCount' => count($followsIndex),
            'reactionsCount' => count($reactionsIndex),
            'postsIndex' => $postsIndex,
            'authoredRepliesIndex' => $authoredRepliesIndex,
            'receivedRepliesIndex' => $receivedRepliesIndex,
            'followersIndex' => $followersIndex,
            'followsIndex' => $followsIndex,
            'reactionsIndex' => $reactionsIndex,
        ];
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function advanceProfile(array $run): array
    {
        $context = is_array($run['context'] ?? null) ? $run['context'] : [];
        $data = is_array($run['data'] ?? null) ? $run['data'] : [];

        $profile = $this->actorResolver->resolve((string) ($context['actor'] ?? ''));
        $actorId = (string) ($profile['did'] ?? $profile['handle'] ?? '');

        if ($actorId === '') {
            return $this->lifecycleManager->markFailed(
                run: $run,
                message: 'Bluesky actor was not found.',
                stage: BlueskyParserStage::Failed->value,
            );
        }

        $data['profile'] = $profile;
        $run['data'] = $data;
        $run['stage'] = BlueskyParserStage::Feed->value;
        $run['progress'] = 5;

        return $run;
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function advanceFeed(array $run): array
    {
        $data = is_array($run['data'] ?? null) ? $run['data'] : [];
        $cursor = is_array($run['cursor'] ?? null) ? $run['cursor'] : [];
        $stats = is_array($run['stats'] ?? null) ? $run['stats'] : [];
        $profile = is_array($data['profile'] ?? null) ? $data['profile'] : [];
        $actor = (string) ($profile['did'] ?? $profile['handle'] ?? '');

        $feed = $this->loadAuthorFeedAction->handle(
            actor: $actor,
            limit: self::FEED_LIMIT,
            cursor: $this->nullableCursor($cursor['feedCursor'] ?? null),
            filter: 'posts_with_replies',
        );

        $expectedDid = (string) ($profile['did'] ?? '');
        $postIds = is_array($data['postIds'] ?? null) ? $data['postIds'] : [];
        $authoredReplyIds = is_array($data['authoredReplyIds'] ?? null) ? $data['authoredReplyIds'] : [];
        $postsIndex = is_array($data['postsIndex'] ?? null) ? $data['postsIndex'] : [];
        $authoredRepliesIndex = is_array($data['authoredRepliesIndex'] ?? null) ? $data['authoredRepliesIndex'] : [];

        foreach ($feed->items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $authorDid = (string) ($item['author']['did'] ?? '');
            if ($expectedDid !== '' && $authorDid !== '' && $authorDid !== $expectedDid) {
                continue;
            }

            $postType = (string) ($item['postType'] ?? 'post');
            $uri = (string) ($item['uri'] ?? $item['id'] ?? '');

            if ($uri === '') {
                continue;
            }

            if ($postType === 'reply') {
                if (isset($authoredReplyIds[$uri])) {
                    continue;
                }

                $authoredReplyIds[$uri] = true;
                $authoredRepliesIndex[] = $item;
            } else {
                if (isset($postIds[$uri])) {
                    continue;
                }

                $postIds[$uri] = true;
                $postsIndex[] = $item;
            }
        }

        $data['postIds'] = $postIds;
        $data['authoredReplyIds'] = $authoredReplyIds;
        $data['postsIndex'] = $postsIndex;
        $data['authoredRepliesIndex'] = $authoredRepliesIndex;
        $run['data'] = $data;

        $stats['processedPosts'] = count($postsIndex);
        $stats['processedAuthoredReplies'] = count($authoredRepliesIndex);
        $run['stats'] = $stats;

        $nextCursor = $feed->pagination['nextCursor'] ?? null;
        if (is_string($nextCursor) && $nextCursor !== '') {
            $cursor['feedCursor'] = $nextCursor;
            $cursor['feedPage'] = (int) ($cursor['feedPage'] ?? 0) + 1;
            $run['progress'] = min(40, 5 + ((int) $cursor['feedPage'] * 4));
        } else {
            $cursor['feedCursor'] = '';
            $run['stage'] = BlueskyParserStage::Followers->value;
            $run['progress'] = 45;
        }

        $run['cursor'] = $cursor;

        return $run;
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function advanceFollowers(array $run): array
    {
        $data = is_array($run['data'] ?? null) ? $run['data'] : [];
        $cursor = is_array($run['cursor'] ?? null) ? $run['cursor'] : [];
        $stats = is_array($run['stats'] ?? null) ? $run['stats'] : [];
        $profile = is_array($data['profile'] ?? null) ? $data['profile'] : [];
        $actor = (string) ($profile['did'] ?? $profile['handle'] ?? '');

        $followers = $this->loadActorFollowersAction->handle(
            actor: $actor,
            limit: self::GRAPH_LIMIT,
            cursor: $this->nullableCursor($cursor['followersCursor'] ?? null),
        );

        $followersIds = is_array($data['followersIds'] ?? null) ? $data['followersIds'] : [];
        $followersIndex = is_array($data['followersIndex'] ?? null) ? $data['followersIndex'] : [];

        foreach ($followers->items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $did = (string) ($item['did'] ?? '');
            if ($did === '' || isset($followersIds[$did])) {
                continue;
            }

            $followersIds[$did] = true;
            $followersIndex[] = $item;
        }

        $data['followersIds'] = $followersIds;
        $data['followersIndex'] = $followersIndex;
        $run['data'] = $data;
        $stats['processedFollowers'] = count($followersIndex);
        $run['stats'] = $stats;

        $nextCursor = $followers->pagination['nextCursor'] ?? null;
        if (is_string($nextCursor) && $nextCursor !== '') {
            $cursor['followersCursor'] = $nextCursor;
            $cursor['followersPage'] = (int) ($cursor['followersPage'] ?? 0) + 1;
            $run['progress'] = min(55, 45 + ((int) $cursor['followersPage'] * 2));
        } else {
            $cursor['followersCursor'] = '';
            $run['stage'] = BlueskyParserStage::Follows->value;
            $run['progress'] = 60;
        }

        $run['cursor'] = $cursor;

        return $run;
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function advanceFollows(array $run): array
    {
        $data = is_array($run['data'] ?? null) ? $run['data'] : [];
        $cursor = is_array($run['cursor'] ?? null) ? $run['cursor'] : [];
        $stats = is_array($run['stats'] ?? null) ? $run['stats'] : [];
        $profile = is_array($data['profile'] ?? null) ? $data['profile'] : [];
        $actor = (string) ($profile['did'] ?? $profile['handle'] ?? '');

        $follows = $this->loadActorFollowsAction->handle(
            actor: $actor,
            limit: self::GRAPH_LIMIT,
            cursor: $this->nullableCursor($cursor['followsCursor'] ?? null),
        );

        $followsIds = is_array($data['followsIds'] ?? null) ? $data['followsIds'] : [];
        $followsIndex = is_array($data['followsIndex'] ?? null) ? $data['followsIndex'] : [];

        foreach ($follows->items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $did = (string) ($item['did'] ?? '');
            if ($did === '' || isset($followsIds[$did])) {
                continue;
            }

            $followsIds[$did] = true;
            $followsIndex[] = $item;
        }

        $data['followsIds'] = $followsIds;
        $data['followsIndex'] = $followsIndex;
        $run['data'] = $data;
        $stats['processedFollows'] = count($followsIndex);
        $run['stats'] = $stats;

        $nextCursor = $follows->pagination['nextCursor'] ?? null;
        if (is_string($nextCursor) && $nextCursor !== '') {
            $cursor['followsCursor'] = $nextCursor;
            $cursor['followsPage'] = (int) ($cursor['followsPage'] ?? 0) + 1;
            $run['progress'] = min(70, 60 + ((int) $cursor['followsPage'] * 2));
        } else {
            $cursor['followsCursor'] = '';
            $cursor['interactionPostIndex'] = 0;
            $cursor['interactionKind'] = 'likes';
            $cursor['interactionCursor'] = '';
            $run['stage'] = BlueskyParserStage::Interactions->value;
            $run['progress'] = 75;
        }

        $run['cursor'] = $cursor;

        return $run;
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function advanceInteractions(array $run): array
    {
        $data = is_array($run['data'] ?? null) ? $run['data'] : [];
        $cursor = is_array($run['cursor'] ?? null) ? $run['cursor'] : [];
        $stats = is_array($run['stats'] ?? null) ? $run['stats'] : [];

        $authoredItems = array_merge(
            is_array($data['postsIndex'] ?? null) ? $data['postsIndex'] : [],
            is_array($data['authoredRepliesIndex'] ?? null) ? $data['authoredRepliesIndex'] : [],
        );

        $postIndex = (int) ($cursor['interactionPostIndex'] ?? 0);
        if ($postIndex >= count($authoredItems)) {
            $run['stage'] = BlueskyParserStage::Finishing->value;
            $run['progress'] = 95;

            return $run;
        }

        $post = is_array($authoredItems[$postIndex] ?? null) ? $authoredItems[$postIndex] : [];
        $postUri = (string) ($post['uri'] ?? '');
        $postCid = (string) ($post['cid'] ?? '');
        $kind = (string) ($cursor['interactionKind'] ?? 'likes');

        if ($postUri === '') {
            $cursor['interactionPostIndex'] = $postIndex + 1;
            $cursor['interactionKind'] = 'likes';
            $cursor['interactionCursor'] = '';
            $run['cursor'] = $cursor;

            return $run;
        }

        return match ($kind) {
            'likes' => $this->collectPostLikes($run, $postUri, $postCid, $postIndex),
            'reposts' => $this->collectPostReposts($run, $postUri, $postCid, $postIndex),
            'replies' => $this->collectPostReplies($run, $postUri, $postIndex),
            default => $this->moveToNextInteractionPost($run, $postIndex),
        };
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function collectPostLikes(array $run, string $postUri, string $postCid, int $postIndex): array
    {
        $data = is_array($run['data'] ?? null) ? $run['data'] : [];
        $cursor = is_array($run['cursor'] ?? null) ? $run['cursor'] : [];
        $stats = is_array($run['stats'] ?? null) ? $run['stats'] : [];

        $likes = $this->loadPostLikesAction->handle(
            uri: $postUri,
            cid: $postCid !== '' ? $postCid : null,
            limit: self::INTERACTION_LIMIT,
            cursor: $this->nullableCursor($cursor['interactionCursor'] ?? null),
        );

        [$data, $stats] = $this->appendReactionItems($data, $stats, $postUri, $postCid, 'like', $likes->items);

        $nextCursor = $likes->pagination['nextCursor'] ?? null;
        if (is_string($nextCursor) && $nextCursor !== '') {
            $cursor['interactionCursor'] = $nextCursor;
        } else {
            $cursor['interactionKind'] = 'reposts';
            $cursor['interactionCursor'] = '';
        }

        $run['data'] = $data;
        $run['stats'] = $stats;
        $run['cursor'] = $cursor;
        $run['progress'] = $this->interactionProgress($data, $postIndex);

        return $run;
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function collectPostReposts(array $run, string $postUri, string $postCid, int $postIndex): array
    {
        $data = is_array($run['data'] ?? null) ? $run['data'] : [];
        $cursor = is_array($run['cursor'] ?? null) ? $run['cursor'] : [];
        $stats = is_array($run['stats'] ?? null) ? $run['stats'] : [];

        $reposts = $this->loadPostRepostsAction->handle(
            uri: $postUri,
            limit: self::INTERACTION_LIMIT,
            cursor: $this->nullableCursor($cursor['interactionCursor'] ?? null),
        );

        [$data, $stats] = $this->appendReactionItems($data, $stats, $postUri, $postCid, 'repost', $reposts->items);

        $nextCursor = $reposts->pagination['nextCursor'] ?? null;
        if (is_string($nextCursor) && $nextCursor !== '') {
            $cursor['interactionCursor'] = $nextCursor;
        } else {
            $cursor['interactionKind'] = 'replies';
            $cursor['interactionCursor'] = '';
        }

        $run['data'] = $data;
        $run['stats'] = $stats;
        $run['cursor'] = $cursor;
        $run['progress'] = $this->interactionProgress($data, $postIndex);

        return $run;
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function collectPostReplies(array $run, string $postUri, int $postIndex): array
    {
        $data = is_array($run['data'] ?? null) ? $run['data'] : [];
        $stats = is_array($run['stats'] ?? null) ? $run['stats'] : [];

        $thread = $this->loadPostThreadAction->handle($postUri, self::THREAD_DEPTH, 0);
        [$data, $stats] = $this->appendReplyItems($data, $stats, $postUri, $thread);

        $run['data'] = $data;
        $run['stats'] = $stats;

        return $this->moveToNextInteractionPost($run, $postIndex);
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function moveToNextInteractionPost(array $run, int $postIndex): array
    {
        $data = is_array($run['data'] ?? null) ? $run['data'] : [];
        $cursor = is_array($run['cursor'] ?? null) ? $run['cursor'] : [];

        $cursor['interactionPostIndex'] = $postIndex + 1;
        $cursor['interactionKind'] = 'likes';
        $cursor['interactionCursor'] = '';
        $run['cursor'] = $cursor;
        $run['progress'] = $this->interactionProgress($data, $postIndex + 1);

        $total = count(array_merge(
            is_array($data['postsIndex'] ?? null) ? $data['postsIndex'] : [],
            is_array($data['authoredRepliesIndex'] ?? null) ? $data['authoredRepliesIndex'] : [],
        ));

        if (($postIndex + 1) >= $total) {
            $run['stage'] = BlueskyParserStage::Finishing->value;
            $run['progress'] = 95;
        }

        return $run;
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function finish(array $run): array
    {
        return $this->lifecycleManager->markCompleted(
            run: $run,
            result: $this->buildResultSnapshot($run),
            stage: BlueskyParserStage::Completed->value,
        );
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $stats
     * @param array<int, array<string, mixed>> $items
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function appendReactionItems(
        array $data,
        array $stats,
        string $postUri,
        string $postCid,
        string $kind,
        array $items,
    ): array {
        $reactionIds = is_array($data['reactionIds'] ?? null) ? $data['reactionIds'] : [];
        $reactionsIndex = is_array($data['reactionsIndex'] ?? null) ? $data['reactionsIndex'] : [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $actor = is_array($item['actor'] ?? null) ? $item['actor'] : [];
            $did = (string) ($actor['did'] ?? '');
            if ($did === '') {
                continue;
            }

            $reactionId = implode(':', [$postUri, $kind, $did, (string) ($item['createdAt'] ?? '')]);
            if (isset($reactionIds[$reactionId])) {
                continue;
            }

            $reactionIds[$reactionId] = true;
            $reactionsIndex[] = [
                'postUri' => $postUri,
                'postCid' => $postCid,
                'kind' => $kind,
                'actor' => $actor,
                'createdAt' => (string) ($item['createdAt'] ?? ''),
                'indexedAt' => (string) ($item['indexedAt'] ?? ''),
            ];
        }

        $data['reactionIds'] = $reactionIds;
        $data['reactionsIndex'] = $reactionsIndex;
        $stats['processedReactions'] = count($reactionsIndex);

        return [$data, $stats];
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $stats
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function appendReplyItems(
        array $data,
        array $stats,
        string $rootPostUri,
        BlueskyThreadResultDTO $thread,
    ): array {
        $receivedReplyIds = is_array($data['receivedReplyIds'] ?? null) ? $data['receivedReplyIds'] : [];
        $receivedRepliesIndex = is_array($data['receivedRepliesIndex'] ?? null) ? $data['receivedRepliesIndex'] : [];

        foreach ($this->flattenReplies($thread->replies) as $reply) {
            $replyUri = (string) ($reply['uri'] ?? '');

            if ($replyUri === '' || isset($receivedReplyIds[$replyUri])) {
                continue;
            }

            $receivedReplyIds[$replyUri] = true;
            $receivedRepliesIndex[] = [
                'rootPostUri' => $rootPostUri,
                ...$reply,
            ];
        }

        $data['receivedReplyIds'] = $receivedReplyIds;
        $data['receivedRepliesIndex'] = $receivedRepliesIndex;
        $stats['processedReceivedReplies'] = count($receivedRepliesIndex);

        return [$data, $stats];
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
     * @param array<string, mixed> $data
     */
    private function interactionProgress(array $data, int $processedPosts): int
    {
        $total = count(array_merge(
            is_array($data['postsIndex'] ?? null) ? $data['postsIndex'] : [],
            is_array($data['authoredRepliesIndex'] ?? null) ? $data['authoredRepliesIndex'] : [],
        ));

        if ($total <= 0) {
            return 95;
        }

        return min(99, 75 + (int) floor((min($processedPosts, $total) / $total) * 24));
    }

    private function nullableCursor(mixed $value): ?string
    {
        $cursor = trim((string) $value);

        return $cursor !== '' ? $cursor : null;
    }
}
