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
        int $maxPages = 10
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

                $comments = $this->loadComments(
                    channelId: $channelId,
                    postId: $postId,
                    limit: $commentsPerRequest,
                    maxPages: $maxPages
                );

                $results[] = [
                    'post_id' => $postId,
                    'post' => $post,
                    'comments' => $comments,
                ];
            } catch (\Throwable $e) {
                $this->logError($e, ['channel' => $channelId, 'post_id' => $postId]);

                $results[] = [
                    'post_id' => $postId,
                    'error' => $e->getMessage(),
                    'comments' => [],
                ];
            }
        }

        return $results;
    }

    private function loadComments(string $channelId, int $postId, int $limit, int $maxPages): array
    {
        $messages = [];
        $offsetId = 0;

        for ($page = 1; $page <= $maxPages; $page++) {
            $response = $this->executeWithRetry(
                callback: fn () => $this->madeline()->messages->getReplies([
                    'peer' => $channelId,
                    'msg_id' => $postId,
                    'offset_id' => $offsetId,
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
            if (empty($batch)) {
                break;
            }

            foreach ($batch as $message) {
                $messages[] = $message;
            }

            $lastMessage = end($batch);
            $offsetId = (int) (
                is_array($lastMessage)
                    ? ($lastMessage['id'] ?? 0)
                    : ($lastMessage->id ?? 0)
            );

            if (count($batch) < $limit || $offsetId <= 0) {
                break;
            }
        }

        return $messages;
    }
}
