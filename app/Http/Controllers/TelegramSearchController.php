<?php

namespace App\Http\Controllers;

use App\Modules\Telegram\Presenters\TelegramMessagePresenter;
use App\Modules\Telegram\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TelegramSearchController extends Controller
{
    public function __construct(
        private readonly TelegramService $telegramService,
        private readonly TelegramMessagePresenter $presenter,
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
        $chatUsername = ltrim(trim((string) $validated['chatUsername']), '@');

        if (isset($validated['dateFrom'], $validated['dateTo']) && $validated['dateFrom'] > $validated['dateTo']) {
            return $this->errorResponse(
                'Дата "с" должна быть меньше или равна дате "по".',
                $limit,
                $offsetId,
                422
            );
        }

        $filter = $this->buildSearchFilter($validated, $limit, $offsetId);
        $dto = $this->telegramService->getMessages($filter);

        if ($dto === null) {
            return $this->errorResponse(
                'Не удалось загрузить сообщения по текущему запросу.',
                $limit,
                $offsetId
            );
        }

        $items = $this->presenter->presentMessages($dto->messages, $chatUsername);
        $nextOffsetId = $this->presenter->resolveNextOffsetId($dto->messages);

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

    public function comments(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'chatUsername' => ['required', 'string', 'max:255'],
            'postId' => ['required', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'offsetId' => ['nullable', 'integer', 'min:0'],
        ]);

        $chatUsername = ltrim(trim((string) $validated['chatUsername']), '@');
        $postId = (int) $validated['postId'];
        $limit = (int) ($validated['limit'] ?? 20);
        $offsetId = (int) ($validated['offsetId'] ?? 0);

        $commentsPage = $this->telegramService->getComments($chatUsername, $postId, $limit, $offsetId);
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

        return response()->json([
            'ok' => true,
            'items' => $items,
            'pagination' => [
                'limit' => $limit,
                'offsetId' => $offsetId,
                'nextOffsetId' => $commentsPage['nextOffsetId'] ?? null,
                'hasMore' => (bool) ($commentsPage['hasMore'] ?? false),
                'total' => (int) ($commentsPage['total'] ?? count($items)),
            ],
        ]);
    }

    private function buildSearchFilter(array $validated, int $limit, int $offsetId): array
    {
        $filter = [
            'peer' => trim((string) $validated['chatUsername']),
            'q' => trim((string) ($validated['q'] ?? '')),
            'limit' => $limit,
            'offset_id' => $offsetId,
        ];

        $fromUsername = trim((string) ($validated['fromUsername'] ?? ''));
        if ($fromUsername !== '') {
            $filter['from_id'] = ltrim($fromUsername, '@');
        }

        if (!empty($validated['dateFrom'])) {
            $filter['min_date'] = Carbon::createFromFormat('Y-m-d', $validated['dateFrom'])->startOfDay()->timestamp;
        }

        if (!empty($validated['dateTo'])) {
            $filter['max_date'] = Carbon::createFromFormat('Y-m-d', $validated['dateTo'])->endOfDay()->timestamp;
        }

        return $filter;
    }

    private function errorResponse(
        string $message,
        int $limit,
        int $offsetId,
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'ok' => false,
            'message' => $message,
            'items' => [],
            'pagination' => [
                'limit' => $limit,
                'offsetId' => $offsetId,
                'nextOffsetId' => null,
                'hasMore' => false,
                'total' => 0,
            ],
        ], $status);
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
