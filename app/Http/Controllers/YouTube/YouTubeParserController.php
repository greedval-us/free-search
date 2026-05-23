<?php

namespace App\Http\Controllers\YouTube;

use App\Http\Controllers\Concerns\HandlesParserDownloads;
use App\Http\Controllers\Controller;
use App\Http\Requests\YouTube\YouTubeParserRequest;
use App\Http\Requests\YouTube\YouTubeParserStartRequest;
use App\Modules\Export\Excel\Contracts\ExcelWorkbookServiceInterface;
use App\Modules\YouTube\Parser\Contracts\YouTubeParserApplicationServiceInterface;
use App\Modules\YouTube\Parser\Contracts\YouTubeParserExportBuilderInterface;
use App\Support\Reports\Contracts\ReportFilenamePolicyInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class YouTubeParserController extends Controller
{
    use HandlesParserDownloads;

    public function __construct(
        private readonly YouTubeParserApplicationServiceInterface $service,
        private readonly YouTubeParserExportBuilderInterface $exportBuilder,
        private readonly ExcelWorkbookServiceInterface $excelWorkbookService,
        private readonly ReportFilenamePolicyInterface $reportFilenamePolicy,
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
        $filename = $this->buildExportFilename(
            $this->reportFilenamePolicy,
            'youtube-parser',
            (string) ($payload['videoId'] ?? 'video'),
            'xlsx'
        );

        return $this->excelWorkbookService->download($filename, $this->exportBuilder->buildSheets($payload));
    }

    public function downloadJson(Request $request, string $runId): StreamedResponse
    {
        $payload = $this->service->getDownloadPayload($this->userId($request), $runId);
        $filename = $this->buildExportFilename(
            $this->reportFilenamePolicy,
            'youtube-parser',
            (string) ($payload['videoId'] ?? 'video'),
            'json'
        );

        return $this->streamJsonDownload($payload, $filename);
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

}
