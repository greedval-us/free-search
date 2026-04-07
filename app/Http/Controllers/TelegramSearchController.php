<?php

namespace App\Http\Controllers;

use App\Modules\Telegram\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TelegramSearchController extends Controller
{
    public function __construct(
        private readonly TelegramService $telegramService,
    ) {
    }

    public function messages(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'chatUsername' => ['required', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:255'],
            'fromUsername' => ['nullable', 'string', 'max:255'],
            'dateFrom' => ['nullable', 'date_format:Y-m-d'],
            'dateTo' => ['nullable', 'date_format:Y-m-d'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offsetId' => ['nullable', 'integer', 'min:0'],
        ]);

        $limit = (int) ($validated['limit'] ?? 20);
        $offsetId = (int) ($validated['offsetId'] ?? 0);

        $filter = [
            'peer' => trim($validated['chatUsername']),
            'q' => trim((string) ($validated['q'] ?? '')),
            'limit' => $limit,
            'offset_id' => $offsetId,
        ];

        $fromUsername = trim((string) ($validated['fromUsername'] ?? ''));
        if ($fromUsername !== '') {
            $filter['from_id'] = ltrim($fromUsername, '@');
        }

        $dateFrom = $validated['dateFrom'] ?? null;
        $dateTo = $validated['dateTo'] ?? null;

        if ($dateFrom !== null && $dateTo !== null && $dateFrom > $dateTo) {
            return response()->json([
                'ok' => false,
                'message' => 'Дата "с" должна быть меньше или равна дате "по".',
                'items' => [],
                'pagination' => [
                    'limit' => $limit,
                    'offsetId' => $offsetId,
                    'nextOffsetId' => null,
                    'hasMore' => false,
                    'total' => 0,
                ],
            ], 422);
        }

        if ($dateFrom !== null) {
            $filter['min_date'] = Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay()->timestamp;
        }

        if ($dateTo !== null) {
            $filter['max_date'] = Carbon::createFromFormat('Y-m-d', $dateTo)->endOfDay()->timestamp;
        }

        $dto = $this->telegramService->getMessages($filter);

        if ($dto === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Не удалось загрузить сообщения по текущему запросу.',
                'items' => [],
                'pagination' => [
                    'limit' => $limit,
                    'offsetId' => $offsetId,
                    'nextOffsetId' => null,
                    'hasMore' => false,
                    'total' => 0,
                ],
            ]);
        }

        $items = [];
        $chatUsername = ltrim(trim($validated['chatUsername']), '@');

        foreach ($dto->messages as $message) {
            $media = $this->extractMediaSummary($message);
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
                'media' => $media,
                'reactions' => $reactions,
                'gifts' => $gifts,
            ];
        }

        $items = $this->normalizeUtf8($items);

        $nextOffsetId = null;
        if (!empty($dto->messages)) {
            $last = end($dto->messages);
            $nextOffsetId = $last?->id ?: null;
        }

        return response()->json([
            'ok' => true,
            'items' => $items,
            'pagination' => [
                'limit' => $limit,
                'offsetId' => $offsetId,
                'nextOffsetId' => $nextOffsetId,
                'hasMore' => $nextOffsetId !== null && count($items) >= $limit,
                'total' => $dto->count,
            ],
        ]);
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

            $t = strtolower($type);
            if (str_contains($t, 'gift') || str_contains($t, 'star')) {
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
