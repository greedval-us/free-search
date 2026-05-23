<?php

namespace App\Http\Controllers\Concerns;

use App\Support\Reports\Contracts\ReportFilenamePolicyInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait HandlesParserDownloads
{
    /**
     * @param array<string, mixed> $payload
     */
    protected function streamJsonDownload(array $payload, string $filename): StreamedResponse
    {
        return response()->streamDownload(
            static function () use ($payload): void {
                echo json_encode(
                    $payload,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
            },
            $filename,
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }

    protected function buildExportFilename(
        ReportFilenamePolicyInterface $policy,
        string $prefix,
        string $target,
        string $extension
    ): string {
        return $policy->buildWithExtension(
            prefix: $prefix,
            target: $target,
            extension: $extension,
        );
    }
}

