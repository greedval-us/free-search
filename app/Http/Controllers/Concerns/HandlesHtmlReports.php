<?php

namespace App\Http\Controllers\Concerns;

use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\View\View;

trait HandlesHtmlReports
{
    /**
     * @param array<string, mixed> $report
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    protected function reportViewData(array $report, array $extra = []): array
    {
        return array_merge([
            'report' => $report,
            'locale' => app()->getLocale(),
            'generatedAt' => Carbon::now(config('app.timezone'))->format($this->reportGeneratedAtFormat()),
        ], $extra);
    }

    /**
     * @param array<string, mixed> $viewData
     */
    protected function htmlReportResponse(
        string $view,
        array $viewData,
        bool $download,
        string $filenamePrefix,
        string $filenameTarget,
    ): View|Response {
        if (!$download) {
            return view($view, $viewData);
        }

        $filename = app(\App\Support\Reports\ReportFilenamePolicy::class)->build(
            prefix: $filenamePrefix,
            target: $filenameTarget,
        );

        return response()
            ->view($view, $viewData)
            ->header('Content-Type', $this->reportContentType())
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    private function reportGeneratedAtFormat(): string
    {
        return (string) config('osint.reports.generated_at_format', 'd.m.Y H:i');
    }

    private function reportContentType(): string
    {
        return (string) config('osint.reports.download_content_type', 'text/html; charset=UTF-8');
    }
}

