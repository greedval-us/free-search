<?php

namespace Tests\Feature;

use App\Http\Controllers\Concerns\HandlesHtmlReports;
use App\Http\Controllers\Concerns\HandlesParserDownloads;
use App\Modules\Export\Excel\ExcelWorkbookService;
use App\Modules\Export\Excel\SheetDefinition;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class DocumentResponsesTest extends TestCase
{
    #[DataProvider('reportModes')]
    public function test_reports_preserve_preview_or_download_with_a_sandbox_policy(bool $download): void
    {
        config()->set('security.headers.enabled', true);
        $this->freezeTime();
        $controller = new class
        {
            use HandlesHtmlReports;

            public function report(bool $download): View|Response
            {
                return $this->htmlReportResponse('reports.site-intel.seo-audit', [
                    'report' => ['target' => ['finalUrl' => '<script>alert(document.cookie)</script>']],
                    'generatedAt' => '2026-10-05 12:00',
                ], $download, 'report', 'example.com');
            }
        };
        Route::middleware('web')->get('/_document-report-test', fn () => $controller->report($download));

        $response = $this->get('/_document-report-test');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertSee('<style>', false)
            ->assertSee('<script>alert(document.cookie)</script>')
            ->assertDontSee('<script>alert(document.cookie)</script>', false);
        $this->assertSame("sandbox; default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'", $response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        if ($download) {
            $response->assertDownload();
        } else {
            $response->assertHeaderMissing('Content-Disposition');
        }
    }

    public static function reportModes(): array
    {
        return ['preview' => [false], 'download' => [true]];
    }

    public function test_json_export_remains_a_download_with_private_security_headers(): void
    {
        config()->set('security.headers.enabled', false);
        $controller = new class
        {
            use HandlesParserDownloads;

            public function download(): StreamedResponse
            {
                return $this->streamJsonDownload(['text' => '<script>alert(1)</script>'], 'results.json');
            }
        };
        Route::middleware('web')->get('/_document-json-test', $controller->download(...));

        $response = $this->get('/_document-json-test');

        $response->assertOk()
            ->assertDownload('results.json')
            ->assertHeader('Content-Type', 'application/json; charset=UTF-8')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame(['text' => '<script>alert(1)</script>'], json_decode($response->streamedContent(), true));
    }

    public function test_excel_export_remains_an_attachment_with_private_security_headers(): void
    {
        config()->set('security.headers.enabled', false);
        Route::middleware('web')->get('/_document-excel-test', fn () => app(ExcelWorkbookService::class)->download('results.xlsx', [
            new SheetDefinition(title: 'Results', headings: ['Text'], rows: [['source content']]),
        ]));

        $response = $this->get('/_document-excel-test');

        try {
            $response->assertOk()
                ->assertDownload('results.xlsx')
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('Content-Security-Policy');
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        } finally {
            @unlink($response->baseResponse->getFile()->getPathname());
        }
    }
}
