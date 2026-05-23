<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesHtmlReports;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

abstract class Controller
{
    use HandlesHtmlReports;

    /**
     * @param array<string, mixed> $payload
     */
    protected function jsonPayload(array $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status);
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function jsonOk(array $payload = [], int $status = 200): JsonResponse
    {
        return $this->jsonPayload(array_merge(['ok' => true], $payload), $status);
    }

    protected function jsonData(mixed $data, int $status = 200): JsonResponse
    {
        return $this->jsonOk(['data' => $data], $status);
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function jsonError(string $message, int $status = 422, array $payload = []): JsonResponse
    {
        return $this->jsonPayload(array_merge([
            'ok' => false,
            'message' => $message,
        ], $payload), $status);
    }

    protected function applyRequestLocale(string $locale): void
    {
        app()->setLocale($locale);
    }

    /**
     * @param array<string, mixed> $report
     * @param array<string, mixed> $extraViewData
     */
    protected function localizedHtmlReportResponse(
        string $locale,
        string $view,
        array $report,
        array $extraViewData = [],
        bool $download = false,
        string $filenamePrefix = 'report',
        string $filenameTarget = 'report',
    ): View|Response {
        $this->applyRequestLocale($locale);

        return $this->htmlReportResponse(
            view: $view,
            viewData: $this->reportViewData($report, $extraViewData),
            download: $download,
            filenamePrefix: $filenamePrefix,
            filenameTarget: $filenameTarget,
        );
    }
}
