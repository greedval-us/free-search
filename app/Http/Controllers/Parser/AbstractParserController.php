<?php

namespace App\Http\Controllers\Parser;

use App\Http\Controllers\Concerns\HandlesParserDownloads;
use App\Http\Controllers\Concerns\ResolvesAuthenticatedUserId;
use App\Http\Controllers\Controller;
use App\Modules\Export\Excel\Contracts\ExcelWorkbookServiceInterface;
use App\Modules\Export\Excel\Contracts\ParserExportBuilderInterface;
use App\Modules\ParserSupport\Contracts\ParserRunApplicationServiceInterface;
use App\Modules\ParserSupport\ParserRunConfig;
use App\Support\Reports\Contracts\ReportFilenamePolicyInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class AbstractParserController extends Controller
{
    use HandlesParserDownloads;
    use ResolvesAuthenticatedUserId;

    public function __construct(
        private readonly ParserRunApplicationServiceInterface $parserApplicationService,
        private readonly ParserExportBuilderInterface $exportBuilder,
        private readonly ExcelWorkbookServiceInterface $excelWorkbookService,
        private readonly ParserRunConfig $parserRunConfig,
        private readonly ReportFilenamePolicyInterface $filenamePolicy,
        private readonly string $exportPrefix,
        private readonly string $exportTargetKey,
        private readonly string $exportTargetFallback,
    ) {}

    final public function status(Request $request, string $runId): JsonResponse
    {
        return $this->jsonPayloadFromOrNotFound(
            $this->parserApplicationService->status($this->userId($request), $runId)
        );
    }

    final public function stop(Request $request, string $runId): JsonResponse
    {
        return $this->jsonPayloadFromOrNotFound(
            $this->parserApplicationService->stop($this->userId($request), $runId)
        );
    }

    final public function history(Request $request): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'items' => $this->parserApplicationService->history($this->userId($request)),
            'retentionDays' => $this->parserRunConfig->retentionDays(),
        ]);
    }

    final public function downloadExcel(Request $request, string $runId): BinaryFileResponse
    {
        $this->applyDownloadLocale($request);

        $payload = $this->downloadPayload($request, $runId);
        $filename = $this->exportFilename($payload, 'xlsx');

        return $this->excelWorkbookService->download($filename, $this->exportBuilder->buildSheets($payload));
    }

    final public function downloadJson(Request $request, string $runId): StreamedResponse
    {
        $this->applyDownloadLocale($request);

        $payload = $this->downloadPayload($request, $runId);

        return $this->streamJsonDownload($payload, $this->exportFilename($payload, 'json'));
    }

    /**
     * @return array<string, mixed>
     */
    private function downloadPayload(Request $request, string $runId): array
    {
        return $this->parserApplicationService->getDownloadPayload($this->userId($request), $runId);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function exportFilename(array $payload, string $extension): string
    {
        return $this->filenamePolicy->buildWithExtension(
            prefix: $this->exportPrefix,
            target: (string) ($payload[$this->exportTargetKey] ?? $this->exportTargetFallback),
            extension: $extension,
        );
    }
}
