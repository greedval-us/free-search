<?php

namespace App\Http\Controllers\YouTube;

use App\Http\Controllers\Controller;
use App\Http\Requests\YouTube\YouTubeParserRequest;
use App\Modules\YouTube\Parser\Contracts\YouTubeParserApplicationServiceInterface;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class YouTubeParserController extends Controller
{
    public function __construct(private readonly YouTubeParserApplicationServiceInterface $service) {}

    public function comments(YouTubeParserRequest $request): JsonResponse
    {
        try {
            return $this->jsonOk(['data' => $this->service->comments($request->toDTO())]);
        } catch (RuntimeException $exception) {
            return $this->jsonError($exception->getMessage(), $this->statusCode($exception));
        }
    }

    private function statusCode(RuntimeException $exception): int
    {
        $code = $exception->getCode();

        return $code >= 400 && $code < 600 ? $code : 422;
    }
}
