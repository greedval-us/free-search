<?php

namespace App\Modules\Telegram\Presenters;

class TelegramMessagePresenter
{
    public function presentMessages(array $messages, string $chatUsername): array
    {
        $items = [];

        foreach ($messages as $message) {
            $reactions = $this->extractReactions($message);
            $gifts = $this->extractGiftSummary($message);

            $items[] = [
                'id' => $message->id,
                'date' => $message->date,
                'message' => $message->message,
                'fromId' => $message->from_id,
                'authorId' => $this->extractAuthorId($message),
                'peerId' => $message->peer_id,
                'views' => $message->views,
                'forwards' => $message->forwards,
                'postAuthor' => $message->post_author,
                'authorSignature' => $message->author_signature,
                'repliesCount' => $message->replies?->replies,
                'telegramUrl' => $this->buildTelegramUrl($chatUsername, $message->id),
                'media' => $this->extractMediaSummary($message),
                'reactions' => $reactions,
                'reactionSenderIds' => $this->extractReactionSenderIds($message),
                'gifts' => $gifts,
            ];
        }

        return $this->normalizeUtf8($items);
    }

    public function resolveNextOffsetId(array $messages): ?int
    {
        if (empty($messages)) {
            return null;
        }

        $last = end($messages);

        return $last?->id ?: null;
    }

    private function buildTelegramUrl(string $chatUsername, int $messageId): ?string
    {
        $chatUsername = trim($chatUsername);
        if ($chatUsername === '' || $messageId <= 0) {
            return null;
        }

        return sprintf('https://t.me/%s/%d', $chatUsername, $messageId);
    }

    private function extractMediaSummary(object $message): array
    {
        $raw = is_array($message->raw ?? null) ? $message->raw : [];
        $mediaType = null;

        if (is_object($message->media) && isset($message->media->media_type_name)) {
            $mediaType = $message->media->media_type_name;
        } elseif (is_array($message->media) && isset($message->media['_'])) {
            $mediaType = (string) $message->media['_'];
        } elseif (isset($raw['media']['_'])) {
            $mediaType = (string) $raw['media']['_'];
        }

        $kind = 'none';
        if ($mediaType !== null) {
            $type = strtolower($mediaType);
            $kind = match (true) {
                str_contains($type, 'photo') => 'photo',
                str_contains($type, 'video') => 'video',
                str_contains($type, 'document') => 'document',
                str_contains($type, 'audio') => 'audio',
                str_contains($type, 'geo') => 'geo',
                str_contains($type, 'poll') => 'poll',
                str_contains($type, 'contact') => 'contact',
                str_contains($type, 'webpage') => 'link_preview',
                default => 'other',
            };
        }

        $labels = [
            'photo' => 'Фото',
            'video' => 'Видео',
            'document' => 'Документ',
            'audio' => 'Аудио',
            'geo' => 'Геопозиция',
            'poll' => 'Опрос',
            'contact' => 'Контакт',
            'link_preview' => 'Ссылка',
            'other' => 'Медиа',
            'none' => 'Нет',
        ];

        return [
            'hasMedia' => $kind !== 'none',
            'type' => $kind,
            'label' => $labels[$kind] ?? 'Медиа',
            'rawType' => $mediaType,
        ];
    }

    private function extractReactions(object $message): array
    {
        $result = [];
        $senderIdsByReaction = $this->extractReactionSenderIdsByReaction($message);

        if (!is_array($message->reactions ?? null)) {
            return $result;
        }

        foreach ($message->reactions as $reaction) {
            $emoji = trim((string) ($reaction->display ?? $reaction->emoticon ?? ''));
            $count = (int) ($reaction->count ?? 0);
            $type = (string) ($reaction->reaction_type ?? '');
            $isPaid = (bool) ($reaction->is_paid ?? false);

            if (($isPaid || str_contains(strtolower($type), 'paid')) && $emoji === '') {
                $emoji = 'Paid';
            }

            if ($emoji === '' && str_contains(strtolower($type), 'custom')) {
                $emoji = 'Custom';
            }

            if ($emoji === '' && $count <= 0) {
                continue;
            }

            $identity = $this->describeReaction([
                'display' => $emoji,
                'reaction_type' => $type,
                'document_id' => $reaction->document_id ?? null,
                'is_paid' => $isPaid,
                'emoticon' => $reaction->emoticon ?? null,
                'reaction' => $reaction->raw['reaction'] ?? null,
            ]);

            $key = $identity['key'] ?? ('label:' . md5($emoji !== '' ? $emoji : 'reaction'));

            $result[] = [
                'key' => $key,
                'emoji' => $identity['label'] ?? ($emoji !== '' ? $emoji : 'Reaction'),
                'count' => $count,
                'senderIds' => $senderIdsByReaction[$key] ?? [],
            ];
        }

        return $result;
    }

    private function extractReactionSenderIds(object $message): array
    {
        return $this->flattenSenderMap($this->extractReactionSenderIdsByReaction($message));
    }

    private function extractGiftSummary(object $message): array
    {
        $raw = is_array($message->raw ?? null) ? $message->raw : [];
        $entries = [];

        $this->collectGiftEntries($raw['action'] ?? null, $entries);
        $this->collectGiftEntries($raw['media'] ?? null, $entries);

        $senderIds = [];
        $types = [];

        foreach ($entries as $entry) {
            $senderIds = array_merge($senderIds, $entry['senderIds']);
            $types[] = $entry['label'];
        }

        return [
            'hasGift' => !empty($entries),
            'types' => array_values(array_unique($types)),
            'senderIds' => array_values(array_unique(array_filter($senderIds, static fn (int $id): bool => $id > 0))),
            'entries' => array_values($entries),
        ];
    }

    private function extractAuthorId(object $message): ?int
    {
        $raw = is_array($message->raw ?? null) ? $message->raw : [];
        $from = is_array($raw['from_id'] ?? null) ? $raw['from_id'] : null;

        if (is_array($from)) {
            foreach (['user_id', 'channel_id', 'chat_id'] as $key) {
                if (isset($from[$key])) {
                    return (int) $from[$key];
                }
            }
        }

        $fallback = (int) ($message->from_id ?? 0);

        return $fallback > 0 ? $fallback : null;
    }

    private function collectSenderIds(mixed $payload): array
    {
        $ids = [];

        if (!is_array($payload)) {
            return $ids;
        }

        foreach ($payload as $key => $value) {
            if (in_array((string) $key, ['from_id', 'peer_id', 'sender_id', 'actor_id', 'user_id', 'channel_id', 'chat_id'], true)) {
                $id = $this->extractPeerId($value);
                if ($id !== null) {
                    $ids[] = $id;
                }
            }

            if (is_array($value)) {
                $ids = array_merge($ids, $this->collectSenderIds($value));
            }
        }

        return $ids;
    }

    private function extractPeerId(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_string($value) && ctype_digit($value)) {
            $id = (int) $value;

            return $id > 0 ? $id : null;
        }

        if (is_array($value)) {
            foreach (['user_id', 'channel_id', 'chat_id'] as $key) {
                if (isset($value[$key])) {
                    $id = (int) $value[$key];

                    return $id > 0 ? $id : null;
                }
            }
        }

        return null;
    }

    private function extractReactionSenderIdsByReaction(object $message): array
    {
        $raw = is_array($message->raw ?? null) ? $message->raw : [];
        $reactions = is_array($raw['reactions'] ?? null) ? $raw['reactions'] : [];
        $map = [];

        foreach (['recent_reactions', 'top_reactors', 'recent_reactors'] as $key) {
            if (!isset($reactions[$key])) {
                continue;
            }

            $this->collectReactionEntries($reactions[$key], $map);
        }

        foreach ($map as $reactionKey => $ids) {
            $map[$reactionKey] = array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));
        }

        return $map;
    }

    private function collectReactionEntries(mixed $payload, array &$map): void
    {
        if (!is_array($payload)) {
            return;
        }

        $identity = $this->describeReaction($payload);
        if ($identity !== null) {
            $map[$identity['key']] = array_merge(
                $map[$identity['key']] ?? [],
                $this->collectSenderIds($payload)
            );
        }

        foreach ($payload as $value) {
            if (is_array($value)) {
                $this->collectReactionEntries($value, $map);
            }
        }
    }

    private function describeReaction(array $payload): ?array
    {
        $reaction = $payload['reaction'] ?? $payload;
        if (!is_array($reaction) && !is_string($reaction)) {
            return null;
        }

        $reactionType = '';
        $emoticon = '';
        $documentId = null;
        $isPaid = false;

        if (is_array($reaction)) {
            $reactionType = (string) ($reaction['_'] ?? $payload['reaction_type'] ?? '');
            $emoticon = trim((string) ($reaction['emoticon'] ?? $payload['emoticon'] ?? $payload['display'] ?? ''));
            $documentId = isset($reaction['document_id']) ? (int) $reaction['document_id'] : null;
            $isPaid = ($reaction['_'] ?? null) === 'reactionPaid' || (bool) ($payload['is_paid'] ?? false);
        } else {
            $reactionType = (string) ($payload['reaction_type'] ?? '');
            $emoticon = trim($reaction);
            $documentId = isset($payload['document_id']) ? (int) $payload['document_id'] : null;
            $isPaid = (bool) ($payload['is_paid'] ?? false);
        }

        $normalizedType = strtolower($reactionType);
        $label = $emoticon;

        if ($label === '' && ($isPaid || str_contains($normalizedType, 'paid'))) {
            $label = 'Paid';
        } elseif ($label === '' && str_contains($normalizedType, 'custom')) {
            $label = 'Custom';
        } elseif ($label === '') {
            $label = 'Reaction';
        }

        $key = match (true) {
            $documentId !== null && $documentId > 0 => 'document:' . $documentId,
            $emoticon !== '' => 'emoji:' . md5($emoticon),
            $isPaid || str_contains($normalizedType, 'paid') => 'paid',
            $reactionType !== '' => 'type:' . $reactionType,
            default => 'label:' . md5($label),
        };

        return [
            'key' => $key,
            'label' => $label,
        ];
    }

    private function collectGiftEntries(mixed $payload, array &$entries): void
    {
        if (!is_array($payload)) {
            return;
        }

        $type = isset($payload['_']) ? (string) $payload['_'] : null;
        if ($type !== null) {
            $normalizedType = strtolower($type);
            if (str_contains($normalizedType, 'gift') || str_contains($normalizedType, 'star')) {
                $key = strtolower($type);
                $entries[$key] = [
                    'key' => $key,
                    'label' => $type,
                    'senderIds' => array_values(array_unique(array_filter(array_merge(
                        $entries[$key]['senderIds'] ?? [],
                        $this->collectSenderIds($payload)
                    ), static fn (int $id): bool => $id > 0))),
                ];
            }
        }

        foreach ($payload as $value) {
            if (is_array($value)) {
                $this->collectGiftEntries($value, $entries);
            }
        }
    }

    private function flattenSenderMap(array $map): array
    {
        $ids = [];

        foreach ($map as $senderIds) {
            if (!is_array($senderIds)) {
                continue;
            }

            $ids = array_merge($ids, $senderIds);
        }

        return array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));
    }

    private function normalizeUtf8(mixed $value): mixed
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->normalizeUtf8($item);
            }

            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        $normalized = @iconv('UTF-8', 'UTF-8//IGNORE', $value);

        return is_string($normalized) ? $normalized : '';
    }
}
