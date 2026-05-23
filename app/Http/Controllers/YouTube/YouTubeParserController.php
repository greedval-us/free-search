<?php

namespace App\Http\Controllers\YouTube;

use App\Http\Controllers\Controller;
use App\Http\Requests\YouTube\YouTubeParserRequest;
use App\Http\Requests\YouTube\YouTubeParserStartRequest;
use App\Modules\Export\Excel\ExcelWorkbookService;
use App\Modules\YouTube\Parser\YouTubeParserExportBuilder;
use App\Modules\YouTube\Parser\Contracts\YouTubeParserApplicationServiceInterface;
use App\Support\Reports\ReportFilenamePolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class YouTubeParserController extends Controller
{
    public function __construct(
        private readonly YouTubeParserApplicationServiceInterface $service,
        private readonly YouTubeParserExportBuilder $exportBuilder,
        private readonly ExcelWorkbookService $excelWorkbookService,
        private readonly ReportFilenamePolicy $reportFilenamePolicy,
    ) {
    }

    public function comments(YouTubeParserRequest $request): JsonResponse
    {
        try {
            return $this->jsonOk(['data' => $this->service->comments($request->toDTO())]);
        } catch (RuntimeException $exception) {
            return $this->jsonError($exception->getMessage(), $this->statusCode($exception));
        }
    }

    public function start(YouTubeParserStartRequest $request): JsonResponse
    {
        return $this->jsonPayload($this->service->start($request->toStartDTO())->toArray());
    }

    public function status(Request $request, string $runId): JsonResponse
    {
        $run = $this->service->status($this->userId($request), $runId);
        abort_unless($run !== null, 404);

        return $this->jsonPayload($run->toArray());
    }

    public function stop(Request $request, string $runId): JsonResponse
    {
        $run = $this->service->stop($this->userId($request), $runId);
        abort_unless($run !== null, 404);

        return $this->jsonPayload($run->toArray());
    }

    public function downloadExcel(Request $request, string $runId): BinaryFileResponse
    {
        $payload = $this->service->getDownloadPayload($this->userId($request), $runId);
        $filename = $this->buildExportFilename('youtube-parser', (string) ($payload['videoId'] ?? 'video'), 'xlsx');

        return $this->excelWorkbookService->download($filename, $this->exportBuilder->buildSheets($payload));
    }

    public function downloadJson(Request $request, string $runId): StreamedResponse
    {
        $payload = $this->service->getDownloadPayload($this->userId($request), $runId);
        $filename = $this->buildExportFilename('youtube-parser', (string) ($payload['videoId'] ?? 'video'), 'json');

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

    private function statusCode(RuntimeException $exception): int
    {
        $code = $exception->getCode();

        return $code >= 400 && $code < 600 ? $code : 422;
    }

    private function userId(Request $request): int
    {
        return (int) $request->user()->id;
    }

    private function buildExportFilename(string $prefix, string $target, string $extension): string
    {
        return $this->reportFilenamePolicy->buildWithExtension(
            prefix: $prefix,
            target: $target,
            extension: $extension,
        );
    }
}
