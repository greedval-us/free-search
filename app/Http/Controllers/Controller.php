<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesHtmlReports;
use App\Support\Contracts\ArrayPayloadable;
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

    protected function jsonDataFrom(ArrayPayloadable $payload, int $status = 200): JsonResponse
    {
        return $this->jsonData($payload->toArray(), $status);
    }

    protected function jsonOkFrom(ArrayPayloadable $payload, int $status = 200): JsonResponse
    {
        return $this->jsonOk($payload->toArray(), $status);
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

    protected function localizedJsonData(string $locale, mixed $data, int $status = 200): JsonResponse
    {
        $this->applyRequestLocale($locale);

        return $this->jsonData($data, $status);
    }

    protected function localizedJsonDataFrom(string $locale, ArrayPayloadable $payload, int $status = 200): JsonResponse
    {
        $this->applyRequestLocale($locale);

        return $this->jsonDataFrom($payload, $status);
    }

    protected function jsonPayloadFrom(ArrayPayloadable $payload): JsonResponse
    {
        return $this->jsonPayload($payload->toArray());
    }

    protected function localizedJsonPayloadFrom(string $locale, ArrayPayloadable $payload): JsonResponse
    {
        $this->applyRequestLocale($locale);

        return $this->jsonPayloadFrom($payload);
    }

    protected function jsonPayloadFromOrNotFound(?ArrayPayloadable $payload): JsonResponse
    {
        abort_unless($payload !== null, 404);

        return $this->jsonPayloadFrom($payload);
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
