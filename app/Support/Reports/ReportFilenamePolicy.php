<?php

namespace App\Support\Reports;

use Carbon\CarbonInterface;

final class ReportFilenamePolicy
{
    public function build(string $prefix, string $target, ?CarbonInterface $now = null): string
    {
        return $this->buildWithExtension($prefix, $target, 'html', $now);
    }

    public function buildWithExtension(
        string $prefix,
        string $target,
        string $extension,
        ?CarbonInterface $now = null,
    ): string
    {
        $timestamp = ($now ?? now(config('app.timezone')))->format($this->timestampFormat());

        return sprintf(
            '%s-%s-%s.%s',
            $this->sanitizePrefix($prefix),
            $this->sanitizeTarget($target),
            $timestamp,
            $this->sanitizeExtension($extension)
        );
    }

    private function sanitizePrefix(string $value): string
    {
        $sanitized = preg_replace('/[^a-z0-9_-]+/i', '-', $value);

        return is_string($sanitized) && $sanitized !== '' ? $sanitized : 'report';
    }

    private function sanitizeTarget(string $value): string
    {
        $sanitized = preg_replace('/[^a-z0-9@._-]+/i', '-', $value);

        return is_string($sanitized) && $sanitized !== '' ? $sanitized : 'report';
    }

    private function timestampFormat(): string
    {
        return (string) config('osint.reports.filename_timestamp_format', 'Ymd-His');
    }

    private function sanitizeExtension(string $value): string
    {
        $normalized = strtolower(ltrim(trim($value), '.'));
        $sanitized = preg_replace('/[^a-z0-9]+/i', '', $normalized);

        return is_string($sanitized) && $sanitized !== '' ? $sanitized : 'html';
    }
}
