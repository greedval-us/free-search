<?php

namespace App\Modules\Telegram\Actions\Request;

use App\Modules\Telegram\Actions\AbstractTelegramAction;

class CommentsAction extends AbstractTelegramAction
{
    public function execute(
        string $channelId,
        array $postIds,
        int $delayMs = 500,
        int $commentsPerRequest = 100,
        int $maxPages = 10,
        int $offsetId = 0
    ): array {
        $results = [];
        $postIds = array_values(
            array_filter(
                array_unique(array_map('intval', $postIds)),
                static fn (int $id): bool => $id > 0
            )
        );

        $commentsPerRequest = max(1, min($commentsPerRequest, 100));
        $maxPages = max(1, $maxPages);
        $delayMs = max(0, $delayMs);

        foreach ($postIds as $postId) {
            try {
                $post = $this->executeWithRetry(
                    callback: fn () => $this->madeline()->channels->getMessages([
                        'channel' => $channelId,
                        'id' => [$postId],
                    ]),
                    context: ['channel' => $channelId, 'post_id' => $postId]
                );

                if ($delayMs > 0) {
                    usleep($delayMs * 1000);
                }

                $commentsPage = $this->loadComments(
                    channelId: $channelId,
                    postId: $postId,
                    limit: $commentsPerRequest,
                    maxPages: $maxPages,
                    offsetId: $offsetId,
                    delayMs: $delayMs,
                );

                $results[] = [
                    'post_id' => $postId,
                    'post' => $post,
                    'comments' => $commentsPage['messages'],
                    'next_offset_id' => $commentsPage['next_offset_id'],
                    'has_more' => $commentsPage['has_more'],
                    'total' => $commentsPage['total'],
                ];
            } catch (\Throwable $e) {
                $this->logError($e, ['channel' => $channelId, 'post_id' => $postId]);

                $results[] = [
                    'post_id' => $postId,
                    'error' => $e->getMessage(),
                    'comments' => [],
                    'next_offset_id' => null,
                    'has_more' => false,
                    'total' => 0,
                ];
            }
        }

        return $results;
    }

    private function loadComments(
        string $channelId,
        int $postId,
        int $limit,
        int $maxPages,
        int $offsetId,
        int $delayMs
    ): array
    {
        $messages = [];
        $nextOffsetId = $offsetId;
        $hasMore = false;
        $total = 0;

        for ($page = 1; $page <= $maxPages; $page++) {
            $response = $this->executeWithRetry(
                callback: fn () => $this->madeline()->messages->getReplies([
                    'peer' => $channelId,
                    'msg_id' => $postId,
                    'offset_id' => $nextOffsetId,
                    'offset_date' => 0,
                    'add_offset' => 0,
                    'limit' => $limit,
                    'max_id' => 0,
                    'min_id' => 0,
                    'hash' => 0,
                ]),
                context: ['channel' => $channelId, 'post_id' => $postId, 'page' => $page]
            );

            $batch = $response['messages'] ?? [];
            $total = (int) ($response['count'] ?? $total);

            if (empty($batch)) {
                break;
            }

            foreach ($batch as $message) {
                $messages[] = $message;
            }

            $lastMessage = end($batch);
            $nextOffsetId = (int) (
                is_array($lastMessage)
                    ? ($lastMessage['id'] ?? 0)
                    : ($lastMessage->id ?? 0)
            );

            $hasMore = count($batch) >= $limit && $nextOffsetId > 0;

            if (!$hasMore || $page >= $maxPages) {
                break;
            }

            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }
        }

        return [
            'messages' => $messages,
            'next_offset_id' => $hasMore ? $nextOffsetId : null,
            'has_more' => $hasMore,
            'total' => $total,
        ];
    }
}
