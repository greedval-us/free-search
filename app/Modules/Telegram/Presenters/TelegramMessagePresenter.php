<?php

namespace App\Modules\Telegram\Presenters;

class TelegramMessagePresenter
{
    public function presentMessages(array $messages, string $chatUsername): array
    {
        $items = [];

        foreach ($messages as $message) {
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
                'reactions' => $this->extractReactions($message),
                'gifts' => $this->extractGiftSummary($message),
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

        if (!is_array($message->reactions ?? null)) {
            return $result;
        }

        foreach ($message->reactions as $reaction) {
            $emoji = trim((string) ($reaction->display ?? $reaction->emoticon ?? ''));
            $count = (int) ($reaction->count ?? 0);
            $type = (string) ($reaction->reaction_type ?? '');
            $isPaid = (bool) ($reaction->is_paid ?? false);

            if (($isPaid || str_contains(strtolower($type), 'paid')) && $emoji === '') {
                $emoji = '⭐';
            }

            if ($emoji === '' && str_contains(strtolower($type), 'custom')) {
                $emoji = '✨';
            }

            if ($emoji === '' && $count <= 0) {
                continue;
            }

            $result[] = [
                'emoji' => $emoji !== '' ? $emoji : 'Реакция',
                'count' => $count,
            ];
        }

        return $result;
    }

    private function extractGiftSummary(object $message): array
    {
        $raw = is_array($message->raw ?? null) ? $message->raw : [];
        $actionType = isset($raw['action']['_']) ? (string) $raw['action']['_'] : null;
        $mediaType = isset($raw['media']['_']) ? (string) $raw['media']['_'] : null;

        $hasGift = false;
        $markers = [];

        foreach ([$actionType, $mediaType] as $type) {
            if (!$type) {
                continue;
            }

            $normalizedType = strtolower($type);
            if (str_contains($normalizedType, 'gift') || str_contains($normalizedType, 'star')) {
                $hasGift = true;
                $markers[] = $type;
            }
        }

        return [
            'hasGift' => $hasGift,
            'types' => array_values(array_unique($markers)),
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
