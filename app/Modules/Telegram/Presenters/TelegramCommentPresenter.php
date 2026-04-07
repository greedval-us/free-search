<?php

namespace App\Modules\Telegram\Presenters;

class TelegramCommentPresenter
{
    public function present(array $commentsPage, int $limit, int $offsetId): array
    {
        $items = [];

        foreach (($commentsPage['items'] ?? []) as $comment) {
            if (!is_array($comment)) {
                continue;
            }

            $id = (int) ($comment['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $items[] = [
                'id' => $id,
                'date' => (int) ($comment['date'] ?? 0),
                'message' => (string) ($comment['message'] ?? ''),
                'authorId' => $this->extractRawAuthorId($comment),
            ];
        }

        return [
            'ok' => true,
            'items' => $items,
            'pagination' => [
                'limit' => $limit,
                'offsetId' => $offsetId,
                'nextOffsetId' => $commentsPage['nextOffsetId'] ?? null,
                'hasMore' => (bool) ($commentsPage['hasMore'] ?? false),
                'total' => (int) ($commentsPage['total'] ?? count($items)),
            ],
        ];
    }

    private function extractRawAuthorId(array $message): ?int
    {
        $from = is_array($message['from_id'] ?? null) ? $message['from_id'] : null;

        if (is_array($from)) {
            foreach (['user_id', 'channel_id', 'chat_id'] as $key) {
                if (isset($from[$key])) {
                    return (int) $from[$key];
                }
            }
        }

        if (isset($message['from_id']) && is_numeric((string) $message['from_id'])) {
            $fallback = (int) $message['from_id'];

            return $fallback > 0 ? $fallback : null;
        }

        return null;
    }
}
