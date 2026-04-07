<?php

namespace App\Modules\Telegram\Actions\Request;

use App\Modules\Telegram\Actions\AbstractTelegramAction;

class CommentsActions extends AbstractTelegramAction
{
    public function execute(string $channelId, array $postIds, int $delayMs = 500): array
    {
        $results = [];

        foreach ($postIds as $postId) {

            try {
                $post = $this->madeline()->channels->getMessages([
                    'channel' => $channelId,
                    'id'      => [$postId]
                ]);

                $postData = [
                    'post_id'   => $postId,
                    'post'      => $post,
                    'comments'  => [],
                ];

                usleep($delayMs * 1000);

                $comments = $this->loadComments($channelId, $postId);

                if (!empty($comments)) {
                    $postData['comments'] = $comments;
                }

                $results[] = $postData;

            } catch (\Exception $e) {
                $this->logError($e, [$channelId, $postId]);

                $results[] = [
                    'post_id'  => $postId,
                    'error'    => $e->getMessage(),
                    'comments' => [],
                ];
            }
        }

        return $results;
    }

    private function loadComments(string $channelId, int $postId): array
    {
        try {
            $response = $this->madeline()->messages->getReplies([
                'peer'          => $channelId,
                'msg_id'        => $postId,
                'offset_id'     => 0,
                'offset_date'   => 0,
                'add_offset'    => 0,
                'limit'         => 100,
                'max_id'        => 0,
                'min_id'        => 0,
                'hash'          => 0,
            ]);

            return $response['messages'] ?? [];

        } catch (\Exception $e) {
            $this->logError($e, [$channelId, $postId]);
            return [];
        }
    }
}
