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
                'reactions' => $this->extractReactions($comment),
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

    /**
     * @param array<string, mixed> $comment
     * @return array<int, array<string, mixed>>
     */
    private function extractReactions(array $comment): array
    {
        $reactions = is_array($comment['reactions'] ?? null) ? $comment['reactions'] : [];
        $results = is_array($reactions['results'] ?? null) ? $reactions['results'] : [];
        $items = [];

        foreach ($results as $result) {
            if (!is_array($result)) {
                continue;
            }

            $reaction = is_array($result['reaction'] ?? null) ? $result['reaction'] : [];
            $emoji = trim((string) ($reaction['emoticon'] ?? ''));
            $count = (int) ($result['count'] ?? 0);
            $documentId = isset($reaction['document_id']) ? (int) $reaction['document_id'] : null;

            if ($emoji === '' && $documentId !== null && $documentId > 0) {
                $emoji = 'Custom';
            }

            if ($emoji === '' && $count <= 0) {
                continue;
            }

            $items[] = [
                'key' => $documentId !== null && $documentId > 0 ? 'document:' . $documentId : 'emoji:' . md5($emoji),
                'emoji' => $emoji !== '' ? $emoji : 'Reaction',
                'count' => $count,
                'senderIds' => [],
            ];
        }

        return $items;
    }
}
