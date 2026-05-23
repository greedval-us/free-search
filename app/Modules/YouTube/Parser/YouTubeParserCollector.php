<?php

namespace App\Modules\YouTube\Parser;

use App\Modules\YouTube\Core\Contracts\YouTubeGatewayInterface;
use App\Modules\YouTube\Presenters\YouTubeCommentThreadPresenter;
use Illuminate\Support\Arr;

class YouTubeParserCollector
{
    private const THREADS_LIMIT = 100;
    private const REPLIES_LIMIT = 100;

    public function __construct(
        private readonly YouTubeGatewayInterface $gateway,
        private readonly YouTubeCommentThreadPresenter $commentThreadPresenter,
    ) {
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    public function advance(array $run): array
    {
        if (($run['status'] ?? null) !== 'running') {
            return $run;
        }

        $stage = (string) ($run['stage'] ?? 'comments');

        return match ($stage) {
            'comments' => $this->advanceComments($run),
            'replies' => $this->advanceReplies($run),
            'finishing' => $this->finish($run),
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
        $stats = is_array($run['stats'] ?? null) ? $run['stats'] : [];
        $data = is_array($run['data'] ?? null) ? $run['data'] : [];

        return [
            'videoId' => (string) ($context['videoId'] ?? ''),
            'commentsCount' => (int) ($stats['processedComments'] ?? 0),
            'repliesCount' => (int) ($stats['processedReplies'] ?? 0),
            'commentsIndex' => array_values(is_array($data['commentsIndex'] ?? null) ? $data['commentsIndex'] : []),
            'repliesIndex' => array_values(is_array($data['repliesIndex'] ?? null) ? $data['repliesIndex'] : []),
        ];
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function advanceComments(array $run): array
    {
        $context = is_array($run['context'] ?? null) ? $run['context'] : [];
        $cursor = is_array($run['cursor'] ?? null) ? $run['cursor'] : [];
        $data = is_array($run['data'] ?? null) ? $run['data'] : [];
        $stats = is_array($run['stats'] ?? null) ? $run['stats'] : [];

        $params = [
            'videoId' => (string) ($context['videoId'] ?? ''),
            'maxResults' => self::THREADS_LIMIT,
        ];

        $pageToken = trim((string) ($cursor['commentsPageToken'] ?? ''));
        if ($pageToken !== '') {
            $params['pageToken'] = $pageToken;
        }

        $payload = $this->gateway->commentThreads($params);
        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];

        $commentIds = is_array($data['commentIds'] ?? null) ? $data['commentIds'] : [];
        $replyIds = is_array($data['replyIds'] ?? null) ? $data['replyIds'] : [];
        $threadParentMap = is_array($data['threadParentMap'] ?? null) ? $data['threadParentMap'] : [];
        $commentsIndex = is_array($data['commentsIndex'] ?? null) ? $data['commentsIndex'] : [];
        $repliesIndex = is_array($data['repliesIndex'] ?? null) ? $data['repliesIndex'] : [];
        $replyThreadIds = is_array($cursor['replyThreadIds'] ?? null) ? array_values($cursor['replyThreadIds']) : [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $presented = $this->commentThreadPresenter->present($item);
            $commentId = (string) ($presented['id'] ?? '');
            $threadId = (string) ($presented['threadId'] ?? '');

            if ($commentId === '' || $threadId === '') {
                continue;
            }

            if (!isset($commentIds[$commentId])) {
                $commentIds[$commentId] = true;
                $commentsIndex[] = [
                    'commentId' => $commentId,
                    'threadId' => $threadId,
                    'videoId' => (string) ($presented['videoId'] ?? ''),
                    'author' => (string) ($presented['author'] ?? ''),
                    'authorChannelUrl' => (string) ($presented['authorChannelUrl'] ?? ''),
                    'text' => (string) ($presented['text'] ?? ''),
                    'likeCount' => (int) ($presented['likeCount'] ?? 0),
                    'publishedAt' => (string) ($presented['publishedAt'] ?? ''),
                    'updatedAt' => (string) ($presented['updatedAt'] ?? ''),
                    'replyCount' => (int) ($presented['replyCount'] ?? 0),
                ];
                $stats['processedComments'] = (int) ($stats['processedComments'] ?? 0) + 1;
            }

            $threadParentMap[$threadId] = $commentId;

            $replyCount = (int) ($presented['replyCount'] ?? 0);
            $embeddedReplies = is_array($presented['replies'] ?? null) ? $presented['replies'] : [];

            if ($replyCount > count($embeddedReplies) && !in_array($threadId, $replyThreadIds, true)) {
                $replyThreadIds[] = $threadId;
            }

            foreach ($embeddedReplies as $reply) {
                if (!is_array($reply)) {
                    continue;
                }

                $replyId = (string) ($reply['id'] ?? '');
                if ($replyId === '' || isset($replyIds[$replyId])) {
                    continue;
                }

                $replyIds[$replyId] = true;
                $repliesIndex[] = [
                    'replyId' => $replyId,
                    'parentCommentId' => $commentId,
                    'threadId' => $threadId,
                    'author' => (string) ($reply['author'] ?? ''),
                    'authorChannelUrl' => '',
                    'text' => (string) ($reply['text'] ?? ''),
                    'likeCount' => (int) ($reply['likeCount'] ?? 0),
                    'publishedAt' => (string) ($reply['publishedAt'] ?? ''),
                    'updatedAt' => (string) ($reply['publishedAt'] ?? ''),
                ];
                $stats['processedReplies'] = (int) ($stats['processedReplies'] ?? 0) + 1;
            }
        }

        $cursor['commentsPage'] = (int) ($cursor['commentsPage'] ?? 0) + 1;
        $cursor['commentsTotalHint'] = (int) Arr::get($payload, 'pageInfo.totalResults', $cursor['commentsTotalHint'] ?? 0);

        $nextPageToken = trim((string) ($payload['nextPageToken'] ?? ''));
        if ($nextPageToken !== '') {
            $cursor['commentsPageToken'] = $nextPageToken;
            $totalHint = max(0, (int) ($cursor['commentsTotalHint'] ?? 0));
            if ($totalHint > 0) {
                $run['progress'] = min(65, max(2, (int) floor((count($commentsIndex) / $totalHint) * 65)));
            } else {
                $run['progress'] = min(65, 2 + ((int) $cursor['commentsPage'] * 6));
            }
        } else {
            $cursor['commentsPageToken'] = '';
            $run['stage'] = count($replyThreadIds) > 0 ? 'replies' : 'finishing';
            $run['progress'] = count($replyThreadIds) > 0 ? 70 : 95;
        }

        $cursor['replyThreadIds'] = $replyThreadIds;
        $data['commentIds'] = $commentIds;
        $data['replyIds'] = $replyIds;
        $data['threadParentMap'] = $threadParentMap;
        $data['commentsIndex'] = $commentsIndex;
        $data['repliesIndex'] = $repliesIndex;
        $run['cursor'] = $cursor;
        $run['data'] = $data;
        $run['stats'] = $stats;

        return $run;
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function advanceReplies(array $run): array
    {
        $cursor = is_array($run['cursor'] ?? null) ? $run['cursor'] : [];
        $data = is_array($run['data'] ?? null) ? $run['data'] : [];
        $stats = is_array($run['stats'] ?? null) ? $run['stats'] : [];

        $replyThreadIds = is_array($cursor['replyThreadIds'] ?? null) ? array_values($cursor['replyThreadIds']) : [];
        $threadIndex = (int) ($cursor['replyThreadIndex'] ?? 0);
        if ($threadIndex >= count($replyThreadIds)) {
            $run['stage'] = 'finishing';
            $run['progress'] = 95;

            return $run;
        }

        $threadId = (string) $replyThreadIds[$threadIndex];
        $threadParentMap = is_array($data['threadParentMap'] ?? null) ? $data['threadParentMap'] : [];
        $parentCommentId = (string) ($threadParentMap[$threadId] ?? '');
        if ($parentCommentId === '') {
            $cursor['replyThreadIndex'] = $threadIndex + 1;
            $cursor['replyPageToken'] = '';
            $run['cursor'] = $cursor;

            return $run;
        }

        $params = [
            'parentId' => $parentCommentId,
            'maxResults' => self::REPLIES_LIMIT,
        ];
        $replyPageToken = trim((string) ($cursor['replyPageToken'] ?? ''));
        if ($replyPageToken !== '') {
            $params['pageToken'] = $replyPageToken;
        }

        $payload = $this->gateway->comments($params);
        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
        $replyIds = is_array($data['replyIds'] ?? null) ? $data['replyIds'] : [];
        $repliesIndex = is_array($data['repliesIndex'] ?? null) ? $data['repliesIndex'] : [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $replyId = (string) ($item['id'] ?? '');
            if ($replyId === '' || isset($replyIds[$replyId])) {
                continue;
            }

            $snippet = is_array($item['snippet'] ?? null) ? $item['snippet'] : [];
            $replyIds[$replyId] = true;
            $repliesIndex[] = [
                'replyId' => $replyId,
                'parentCommentId' => $parentCommentId,
                'threadId' => $threadId,
                'author' => (string) ($snippet['authorDisplayName'] ?? ''),
                'authorChannelUrl' => (string) ($snippet['authorChannelUrl'] ?? ''),
                'text' => (string) ($snippet['textDisplay'] ?? ''),
                'likeCount' => (int) ($snippet['likeCount'] ?? 0),
                'publishedAt' => (string) ($snippet['publishedAt'] ?? ''),
                'updatedAt' => (string) ($snippet['updatedAt'] ?? ''),
            ];
            $stats['processedReplies'] = (int) ($stats['processedReplies'] ?? 0) + 1;
        }

        $nextPageToken = trim((string) ($payload['nextPageToken'] ?? ''));
        if ($nextPageToken !== '') {
            $cursor['replyPageToken'] = $nextPageToken;
        } else {
            $cursor['replyThreadIndex'] = $threadIndex + 1;
            $cursor['replyPageToken'] = '';
        }

        $processedThreads = min((int) ($cursor['replyThreadIndex'] ?? 0), count($replyThreadIds));
        $totalThreads = max(1, count($replyThreadIds));
        $run['progress'] = min(99, 70 + (int) floor(($processedThreads / $totalThreads) * 29));
        if ($processedThreads >= count($replyThreadIds) && $nextPageToken === '') {
            $run['stage'] = 'finishing';
            $run['progress'] = 95;
        }

        $data['replyIds'] = $replyIds;
        $data['repliesIndex'] = $repliesIndex;
        $run['data'] = $data;
        $run['cursor'] = $cursor;
        $run['stats'] = $stats;

        return $run;
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function finish(array $run): array
    {
        $run['result'] = $this->buildResultSnapshot($run);
        $run['status'] = 'completed';
        $run['stage'] = 'completed';
        $run['progress'] = 100;
        $run['error'] = null;

        return $run;
    }
}
