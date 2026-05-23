<?php

namespace App\Http\Controllers\YouTube;

use App\Http\Controllers\Concerns\HandlesParserDownloads;
use App\Http\Controllers\Concerns\ResolvesAuthenticatedUserId;
use App\Http\Controllers\Concerns\ResolvesHttpStatusCodeFromException;
use App\Http\Controllers\Controller;
use App\Http\Requests\YouTube\YouTubeParserRequest;
use App\Http\Requests\YouTube\YouTubeParserStartRequest;
use App\Modules\Export\Excel\Contracts\ExcelWorkbookServiceInterface;
use App\Modules\YouTube\Parser\Contracts\YouTubeParserApplicationServiceInterface;
use App\Modules\YouTube\Parser\Contracts\YouTubeParserExportBuilderInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class YouTubeParserController extends Controller
{
    use HandlesParserDownloads;
    use ResolvesAuthenticatedUserId;
    use ResolvesHttpStatusCodeFromException;

    public function __construct(
        private readonly YouTubeParserApplicationServiceInterface $parserApplicationService,
        private readonly YouTubeParserExportBuilderInterface $exportBuilder,
        private readonly ExcelWorkbookServiceInterface $excelWorkbookService,
    ) {
    }

    public function comments(YouTubeParserRequest $request): JsonResponse
    {
        try {
            return $this->jsonData($this->parserApplicationService->comments($request->toDTO()));
        } catch (RuntimeException $exception) {
            return $this->jsonError($exception->getMessage(), $this->statusCodeFromException($exception));
        }
    }

    public function start(YouTubeParserStartRequest $request): JsonResponse
    {
        return $this->jsonPayloadFrom($this->parserApplicationService->start($request->toStartDTO()));
    }

    public function status(Request $request, string $runId): JsonResponse
    {
        return $this->jsonPayloadFromOrNotFound(
            $this->parserApplicationService->status($this->userId($request), $runId)
        );
    }

    public function stop(Request $request, string $runId): JsonResponse
    {
        return $this->jsonPayloadFromOrNotFound(
            $this->parserApplicationService->stop($this->userId($request), $runId)
        );
    }

    public function downloadExcel(Request $request, string $runId): BinaryFileResponse
    {
        $payload = $this->parserApplicationService->getDownloadPayload($this->userId($request), $runId);
        $filename = $this->buildExportFilename(
            'youtube-parser',
            (string) ($payload['videoId'] ?? 'video'),
            'xlsx'
        );

        return $this->excelWorkbookService->download($filename, $this->exportBuilder->buildSheets($payload));
    }

    public function downloadJson(Request $request, string $runId): StreamedResponse
    {
        $payload = $this->parserApplicationService->getDownloadPayload($this->userId($request), $runId);
        $filename = $this->buildExportFilename(
            'youtube-parser',
            (string) ($payload['videoId'] ?? 'video'),
            'json'
        );

        return $this->streamJsonDownload($payload, $filename);
    }

}
