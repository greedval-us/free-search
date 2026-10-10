<?php

namespace App\Http\Controllers\Concerns;

use App\Modules\Export\JsonExportEncoder;
use App\Support\Http\DocumentResponseHeaders;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

trait HandlesParserDownloads
{
    /**
     * @var array<int, string>
     */
    private const SUPPORTED_DOWNLOAD_LOCALES = ['ru', 'en'];

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function streamJsonDownload(array $payload, string $filename): StreamedResponse
    {
        $stream = app(JsonExportEncoder::class)->prepare($payload);

        try {
            return response()->streamDownload(
                static function () use ($stream): void {
                    try {
                        fpassthru($stream);
                    } finally {
                        fclose($stream);
                    }
                },
                $filename,
                [...DocumentResponseHeaders::download(), 'Content-Type' => 'application/json; charset=UTF-8']
            );
        } catch (Throwable $exception) {
            fclose($stream);

            throw $exception;
        }
    }

    protected function applyDownloadLocale(Request $request): void
    {
        $locale = strtolower(trim((string) $request->query('locale', app()->getLocale())));

        app()->setLocale(
            in_array($locale, self::SUPPORTED_DOWNLOAD_LOCALES, true) ? $locale : 'en',
        );
    }
}
