<?php

namespace App\Http\Controllers;

use App\Http\Requests\Telegram\SearchCommentsRequest;
use App\Http\Requests\Telegram\SearchMessagesRequest;
use App\Http\Requests\Telegram\StreamTelegramMediaRequest;
use App\Modules\Telegram\Presenters\TelegramCommentPresenter;
use App\Modules\Telegram\Presenters\TelegramMessagePresenter;
use App\Modules\Telegram\Support\TelegramMediaResponder;
use App\Modules\Telegram\TelegramService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TelegramSearchController extends Controller
{
    public function __construct(
        private readonly TelegramService $telegramService,
        private readonly TelegramMessagePresenter $presenter,
        private readonly TelegramCommentPresenter $commentPresenter,
        private readonly TelegramMediaResponder $mediaResponder,
    ) {
    }

    public function messages(SearchMessagesRequest $request): JsonResponse
    {
        $limit = $request->limitValue();
        $offsetId = $request->offsetId();
        $chatUsername = $request->chatUsername();
        $dto = $this->telegramService->getMessages($request->telegramFilter());

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

    public function comments(SearchCommentsRequest $request): JsonResponse
    {
        $limit = $request->limitValue();
        $offsetId = $request->offsetId();
        $commentsPage = $this->telegramService->getComments(
            $request->chatUsername(),
            $request->postId(),
            $limit,
            $offsetId
        );

        return response()->json($this->commentPresenter->present($commentsPage, $limit, $offsetId));
    }

    public function media(StreamTelegramMediaRequest $request): BinaryFileResponse
    {
        $chatUsername = $request->chatUsername();
        $messageId = $request->messageId();

        if ($chatUsername === '' || $messageId <= 0) {
            abort(404);
        }

        $media = $this->telegramService->getMessageMedia($chatUsername, $messageId);

        if ($media === null) {
            abort(404);
        }

        return $this->mediaResponder->respond($media);
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
}
