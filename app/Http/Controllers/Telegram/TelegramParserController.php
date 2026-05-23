<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Concerns\HandlesParserDownloads;
use App\Http\Requests\Telegram\TelegramParserStartRequest;
use App\Modules\Export\Excel\Contracts\ExcelWorkbookServiceInterface;
use App\Modules\Telegram\Parser\Contracts\TelegramParserApplicationServiceInterface;
use App\Modules\Telegram\Parser\Contracts\TelegramParserExportBuilderInterface;
use App\Support\Reports\Contracts\ReportFilenamePolicyInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TelegramParserController extends BaseTelegramController
{
    use HandlesParserDownloads;

    public function __construct(
        private readonly TelegramParserApplicationServiceInterface $parserApplicationService,
        private readonly TelegramParserExportBuilderInterface $exportBuilder,
        private readonly ExcelWorkbookServiceInterface $excelWorkbookService,
        private readonly ReportFilenamePolicyInterface $reportFilenamePolicy,
    ) {
    }

    public function start(TelegramParserStartRequest $request): JsonResponse
    {
        return $this->jsonPayload($this->parserApplicationService->start($request->toStartDTO())->toArray());
    }

    public function status(Request $request, string $runId): JsonResponse
    {
        $run = $this->parserApplicationService->status($this->userId($request), $runId);
        abort_unless($run !== null, 404);

        return $this->jsonPayload($run->toArray());
    }

    public function stop(Request $request, string $runId): JsonResponse
    {
        $run = $this->parserApplicationService->stop($this->userId($request), $runId);
        abort_unless($run !== null, 404);

        return $this->jsonPayload($run->toArray());
    }

    public function downloadExcel(Request $request, string $runId): BinaryFileResponse
    {
        $payload = $this->parserApplicationService->getDownloadPayload($this->userId($request), $runId);
        $filename = $this->buildExportFilename(
            $this->reportFilenamePolicy,
            'telegram-parser',
            (string) ($payload['chatUsername'] ?? 'chat'),
            'xlsx'
        );

        return $this->excelWorkbookService->download($filename, $this->exportBuilder->buildSheets($payload));
    }

    public function downloadJson(Request $request, string $runId): StreamedResponse
    {
        $payload = $this->parserApplicationService->getDownloadPayload($this->userId($request), $runId);
        $filename = $this->buildExportFilename(
            $this->reportFilenamePolicy,
            'telegram-parser',
            (string) ($payload['chatUsername'] ?? 'chat'),
            'json'
        );

        return $this->streamJsonDownload($payload, $filename);
    }
}
