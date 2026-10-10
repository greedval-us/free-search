<?php

namespace Tests\Feature;

use App\Exceptions\Public\ExternalServiceUnavailableException;
use App\Http\Controllers\Concerns\HandlesParserDownloads;
use App\Http\Controllers\Parser\AbstractParserController;
use App\Models\User;
use App\Modules\Export\Excel\Contracts\ExcelWorkbookServiceInterface;
use App\Modules\Export\Excel\Contracts\ParserExportBuilderInterface;
use App\Modules\Export\Excel\ExcelWorkbookService;
use App\Modules\Export\Excel\SheetDefinition;
use App\Modules\ParserSupport\Contracts\ParserRunApplicationServiceInterface;
use App\Modules\ParserSupport\ParserRunConfig;
use App\Support\Reports\Contracts\ReportFilenamePolicyInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class ParserExportBudgetTest extends TestCase
{
    public function test_json_budget_fails_before_download_headers_or_partial_output(): void
    {
        config()->set('osint.parser_runs.limits.max_export_bytes', 13);
        app()->forgetInstance(ParserRunConfig::class);
        $this->jsonRoute(['a' => 1]);

        $this->getJson('/_parser-budget-json')
            ->assertServiceUnavailable()
            ->assertJsonPath('code', 'parser_export_limit_exceeded')
            ->assertHeaderMissing('Content-Disposition');
    }

    #[DataProvider('jsonBudgets')]
    public function test_json_download_keeps_exact_pretty_bytes_at_and_below_the_budget(int $limit): void
    {
        config()->set('osint.parser_runs.limits.max_export_bytes', $limit);
        app()->forgetInstance(ParserRunConfig::class);
        $this->jsonRoute(['a' => 1]);

        $response = $this->get('/_parser-budget-json')->assertDownload('results.json');

        $this->assertSame("{\n    \"a\": 1\n}", $response->streamedContent());
    }

    public static function jsonBudgets(): array
    {
        return [[14], [15]];
    }

    public function test_excel_cell_budget_rejects_before_building_a_workbook(): void
    {
        config()->set('osint.parser_runs.limits.max_export_cells', 2);
        app()->forgetInstance(ParserRunConfig::class);
        Excel::shouldReceive('download')->never();
        $this->excelRoute();

        $response = $this->getJson('/_parser-budget-excel');

        try {
            $response->assertServiceUnavailable()
                ->assertJsonPath('code', 'parser_export_limit_exceeded')
                ->assertHeaderMissing('Content-Disposition');
        } finally {
            if ($response->baseResponse instanceof BinaryFileResponse) {
                @unlink($response->baseResponse->getFile()->getPathname());
            }
        }
    }

    public function test_excel_with_exact_cell_budget_still_downloads_a_complete_workbook(): void
    {
        config()->set('osint.parser_runs.limits.max_export_cells', 3);
        app()->forgetInstance(ParserRunConfig::class);
        $this->excelRoute();

        $response = $this->get('/_parser-budget-excel');

        try {
            $response->assertDownload('results.xlsx');
            $this->assertGreaterThan(0, $response->baseResponse->getFile()->getSize());
        } finally {
            @unlink($response->baseResponse->getFile()->getPathname());
        }
    }

    public function test_excel_text_byte_budget_rejects_before_building_a_workbook(): void
    {
        config()->set('osint.parser_runs.limits.max_export_bytes', 5);
        app()->forgetInstance(ParserRunConfig::class);
        Excel::shouldReceive('download')->never();
        Route::middleware('web')->get('/_parser-budget-text', fn () => app(ExcelWorkbookService::class)->download('results.xlsx', [
            new SheetDefinition('Results', ['A'], [['12345']]),
        ]));

        $this->getJson('/_parser-budget-text')->assertServiceUnavailable()
            ->assertJsonPath('code', 'parser_export_limit_exceeded')
            ->assertHeaderMissing('Content-Disposition');
    }

    public function test_controller_checks_payload_before_module_builder_expands_it_into_sheets(): void
    {
        config()->set('osint.parser_runs.limits.max_export_bytes', 5);
        app()->forgetInstance(ParserRunConfig::class);
        $application = $this->mock(ParserRunApplicationServiceInterface::class);
        $application->shouldReceive('getDownloadPayload')->once()->with(17, 'run-id')
            ->andReturn(['text' => '123456']);
        $builder = $this->mock(ParserExportBuilderInterface::class);
        $builder->shouldNotReceive('buildSheets');
        $workbook = $this->mock(ExcelWorkbookServiceInterface::class);
        $workbook->shouldNotReceive('download');
        $filenames = $this->mock(ReportFilenamePolicyInterface::class);
        $filenames->shouldNotReceive('buildWithExtension');
        $controller = new class($application, $builder, $workbook, app(ParserRunConfig::class), $filenames, 'parser', 'target', 'result') extends AbstractParserController {};
        $request = Request::create('/');
        $request->setUserResolver(static fn (): User => (new User)->forceFill(['id' => 17]));

        $this->expectException(ExternalServiceUnavailableException::class);

        $controller->downloadExcel($request, 'run-id');
    }

    #[DataProvider('artifactSizes')]
    public function test_excel_artifact_byte_budget_is_checked_before_sending_and_failed_file_is_deleted(int $size, bool $accepted): void
    {
        config()->set('osint.parser_runs.limits.max_export_bytes', 5);
        app()->forgetInstance(ParserRunConfig::class);
        $path = tempnam(sys_get_temp_dir(), 'parser-export-size-');
        file_put_contents($path, str_repeat('x', $size));
        Excel::shouldReceive('download')->once()->andReturn(response()->download($path, 'results.xlsx'));
        Route::middleware('web')->get('/_parser-budget-artifact', fn () => app(ExcelWorkbookService::class)->download('results.xlsx', [
            new SheetDefinition('Results', ['A'], [['x']]),
        ]));

        try {
            $response = $this->getJson('/_parser-budget-artifact');
            if ($accepted) {
                $response->assertDownload('results.xlsx');
                $this->assertFileExists($path);
            } else {
                $response->assertServiceUnavailable()->assertJsonPath('code', 'parser_export_limit_exceeded');
                $this->assertFileDoesNotExist($path);
            }
        } finally {
            @unlink($path);
        }
    }

    public static function artifactSizes(): array
    {
        return [[4, true], [5, true], [6, false]];
    }

    public function test_json_response_callback_closes_the_prepared_temporary_file_after_sending(): void
    {
        $before = get_resources('stream');
        $controller = new class
        {
            use HandlesParserDownloads;

            public function download(): StreamedResponse
            {
                return $this->streamJsonDownload(['a' => 1], 'results.json');
            }
        };
        $response = $controller->download();
        $created = array_diff_key(get_resources('stream'), $before);
        $this->assertCount(1, $created);
        $stream = array_values($created)[0];
        $path = stream_get_meta_data($stream)['uri'];

        ob_start();
        try {
            $response->sendContent();
            $output = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        $this->assertSame("{\n    \"a\": 1\n}", $output);
        $this->assertFalse(is_resource($stream));
        $this->assertFileDoesNotExist($path);
    }

    public function test_json_response_construction_closes_its_stream_when_download_headers_are_invalid(): void
    {
        $controller = new class
        {
            use HandlesParserDownloads;

            public function download(): StreamedResponse
            {
                return $this->streamJsonDownload(['a' => 1], 'invalid/name.json');
            }
        };
        $streamsBefore = array_keys(get_resources('stream'));

        try {
            $controller->download();
            $this->fail('Invalid download headers must be rejected.');
        } catch (InvalidArgumentException) {
            $this->assertSame($streamsBefore, array_keys(get_resources('stream')));
        }
    }

    private function jsonRoute(array $payload): void
    {
        $controller = new class
        {
            use HandlesParserDownloads;

            public function download(array $payload): StreamedResponse
            {
                return $this->streamJsonDownload($payload, 'results.json');
            }
        };
        Route::middleware('web')->get('/_parser-budget-json', fn () => $controller->download($payload));
    }

    private function excelRoute(): void
    {
        Route::middleware('web')->get('/_parser-budget-excel', fn () => app(ExcelWorkbookService::class)->download('results.xlsx', [
            new SheetDefinition('Results', ['Text'], [['x'], ['y']]),
        ]));
    }
}
