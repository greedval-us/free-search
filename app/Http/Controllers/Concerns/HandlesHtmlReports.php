<?php

namespace App\Http\Controllers\Concerns;

use App\Support\Reports\Contracts\ReportFilenamePolicyInterface;
use App\Support\Reports\ReportFilenamePolicy;
use App\Support\Reports\ReportsConfig;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
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
            'locale' => App::currentLocale(),
            'generatedAt' => Carbon::now($this->htmlReportsConfig()->timezone())->format($this->reportGeneratedAtFormat()),
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

        $filename = $this->htmlReportFilenamePolicy()->build(
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
        return $this->htmlReportsConfig()->generatedAtFormat();
    }

    private function reportContentType(): string
    {
        return $this->htmlReportsConfig()->downloadContentType();
    }

    private function htmlReportFilenamePolicy(): ReportFilenamePolicyInterface
    {
        return new ReportFilenamePolicy($this->htmlReportsConfig());
    }

    private function htmlReportsConfig(): ReportsConfig
    {
        return ReportsConfig::fromArray(
            (array) config('osint.reports', []),
            (string) config('app.timezone', 'UTC')
        );
    }
}
