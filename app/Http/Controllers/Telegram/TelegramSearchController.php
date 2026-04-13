<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Http\Requests\Telegram\SearchCommentsRequest;
use App\Http\Requests\Telegram\SearchMessagesRequest;
use App\Http\Requests\Telegram\StreamTelegramMediaRequest;
use App\Modules\Telegram\Search\TelegramSearchQueryService;
use App\Modules\Telegram\Support\TelegramMediaResponder;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TelegramSearchController extends Controller
{
    public function __construct(
        private readonly TelegramSearchQueryService $searchQueryService,
        private readonly TelegramMediaResponder $mediaResponder,
    ) {
    }

    public function messages(SearchMessagesRequest $request): JsonResponse
    {
        return response()->json($this->searchQueryService->messages($request->toQueryDTO()));
    }

    public function comments(SearchCommentsRequest $request): JsonResponse
    {
        return response()->json($this->searchQueryService->comments($request->toQueryDTO()));
    }

    public function media(StreamTelegramMediaRequest $request): BinaryFileResponse
    {
        $media = $this->searchQueryService->media($request->toQueryDTO());

        if ($media === null) {
            abort(404);
        }

        return $this->mediaResponder->respond($media);
    }
}
