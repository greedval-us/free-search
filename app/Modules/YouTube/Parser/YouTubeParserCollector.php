<?php

namespace App\Modules\YouTube\Parser;

use App\Modules\YouTube\DTO\Parser\YouTubeParserStateDTO;
use App\Modules\YouTube\Core\Contracts\YouTubeGatewayInterface;
use App\Modules\YouTube\Enums\YouTubeParserStage;
use App\Modules\YouTube\Presenters\YouTubeCommentThreadPresenter;
use Illuminate\Support\Arr;

class YouTubeParserCollector
{
    private const THREADS_LIMIT = 100;
    private const REPLIES_LIMIT = 100;

    public function __construct(
        private readonly YouTubeGatewayInterface $gateway,
        private readonly YouTubeCommentThreadPresenter $commentThreadPresenter,
        private readonly YouTubeParserSnapshotBuilder $snapshotBuilder,
    ) {
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    public function advance(array $run): array
    {
        $state = YouTubeParserStateDTO::fromArray($run);

        if (! $state->isRunning()) {
            return $state->toArray();
        }

        return match ($state->stage()) {
            YouTubeParserStage::Comments => $this->advanceComments($state),
            YouTubeParserStage::Replies => $this->advanceReplies($state),
            YouTubeParserStage::Finishing => $this->finish($state),
            default => $state->toArray(),
        };
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    public function buildResultSnapshot(array $run): array
    {
        $state = YouTubeParserStateDTO::fromArray($run);

        return $this->snapshotBuilder->build($state->videoId(), $state->data())->toArray();
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function advanceComments(YouTubeParserStateDTO $state): array
    {
        $cursor = $state->cursor();
        $data = $state->data();

        $params = [
            'videoId' => $state->videoId(),
            'maxResults' => self::THREADS_LIMIT,
        ];

        $pageToken = $cursor->commentsPageToken();
        if ($pageToken !== null) {
            $params['pageToken'] = $pageToken;
        }

        $payload = $this->gateway->commentThreads($params);
        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $presented = $this->commentThreadPresenter->present($item);
            $commentId = (string) ($presented['id'] ?? '');
            $threadId = (string) ($presented['threadId'] ?? '');

            if ($commentId === '' || $threadId === '') {
                continue;
            }

            $data->appendCommentThread($presented, (string) ($presented['videoId'] ?? ''));

            $replyCount = (int) ($presented['replyCount'] ?? 0);
            $embeddedReplies = is_array($presented['replies'] ?? null) ? $presented['replies'] : [];

            if ($replyCount > count($embeddedReplies)) {
                $cursor->addReplyThreadId($threadId);
            }

            foreach ($embeddedReplies as $reply) {
                if (! is_array($reply)) {
                    continue;
                }

                $data->appendEmbeddedReply($reply, $commentId, $threadId);
            }
        }

        $cursor->incrementCommentsPage();
        $cursor->setCommentsTotalHint((int) Arr::get($payload, 'pageInfo.totalResults', $cursor->commentsTotalHint()));

        $nextPageToken = trim((string) ($payload['nextPageToken'] ?? ''));
        if ($nextPageToken !== '') {
            $cursor->setCommentsPageToken($nextPageToken);
            $totalHint = $cursor->commentsTotalHint();
            if ($totalHint > 0) {
                $state->setProgress(min(65, max(2, (int) floor(($data->commentsCount() / $totalHint) * 65))));
            } else {
                $state->setProgress(min(65, 2 + ($cursor->commentsPage() * 6)));
            }
        } else {
            $cursor->setCommentsPageToken(null);
            $state->setStage(count($cursor->replyThreadIds()) > 0 ? YouTubeParserStage::Replies : YouTubeParserStage::Finishing);
            $state->setProgress(count($cursor->replyThreadIds()) > 0 ? 70 : 95);
        }

        return $state->toArray();
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function advanceReplies(YouTubeParserStateDTO $state): array
    {
        $cursor = $state->cursor();
        $data = $state->data();

        $replyThreadIds = $cursor->replyThreadIds();
        $threadIndex = $cursor->replyThreadIndex();
        if ($threadIndex >= count($replyThreadIds)) {
            $state->setStage(YouTubeParserStage::Finishing);
            $state->setProgress(95);

            return $state->toArray();
        }

        $threadId = (string) $replyThreadIds[$threadIndex];
        $parentCommentId = $data->threadParentId($threadId);
        if ($parentCommentId === '') {
            $cursor->setReplyThreadIndex($threadIndex + 1);
            $cursor->setReplyPageToken(null);

            return $state->toArray();
        }

        $params = [
            'parentId' => $parentCommentId,
            'maxResults' => self::REPLIES_LIMIT,
        ];
        $replyPageToken = $cursor->replyPageToken();
        if ($replyPageToken !== null) {
            $params['pageToken'] = $replyPageToken;
        }

        $payload = $this->gateway->comments($params);
        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $replyId = (string) ($item['id'] ?? '');
            $snippet = is_array($item['snippet'] ?? null) ? $item['snippet'] : [];

            $data->appendReplySnippet($snippet, $replyId, $parentCommentId, $threadId);
        }

        $nextPageToken = trim((string) ($payload['nextPageToken'] ?? ''));
        if ($nextPageToken !== '') {
            $cursor->setReplyPageToken($nextPageToken);
        } else {
            $cursor->setReplyThreadIndex($threadIndex + 1);
            $cursor->setReplyPageToken(null);
        }

        $processedThreads = min($cursor->replyThreadIndex(), count($replyThreadIds));
        $totalThreads = max(1, count($replyThreadIds));
        $state->setProgress(min(99, 70 + (int) floor(($processedThreads / $totalThreads) * 29)));
        if ($processedThreads >= count($replyThreadIds) && $nextPageToken === '') {
            $state->setStage(YouTubeParserStage::Finishing);
            $state->setProgress(95);
        }

        return $state->toArray();
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function finish(YouTubeParserStateDTO $state): array
    {
        $state->complete(
            result: $this->snapshotBuilder->build($state->videoId(), $state->data())->toArray(),
            stage: YouTubeParserStage::Completed,
        );

        return $state->toArray();
    }
}
