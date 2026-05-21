<?php

namespace App\Http\Controllers\YouTube;

use App\Http\Controllers\Controller;
use App\Http\Requests\YouTube\YouTubeAnalyticsRequest;
use App\Modules\YouTube\Analytics\Contracts\YouTubeAnalyticsApplicationServiceInterface;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class YouTubeAnalyticsController extends Controller
{
    public function __construct(private readonly YouTubeAnalyticsApplicationServiceInterface $service) {}

    public function summary(YouTubeAnalyticsRequest $request): JsonResponse
    {
        try {
            return $this->jsonOk(['data' => $this->service->summary($request->toDTO())]);
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
