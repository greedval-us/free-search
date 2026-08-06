<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Concerns\HandlesParserDownloads;
use App\Http\Requests\Telegram\TelegramParserStartRequest;
use App\Modules\Export\Excel\Contracts\ExcelWorkbookServiceInterface;
use App\Modules\Telegram\Parser\Contracts\TelegramParserApplicationServiceInterface;
use App\Modules\Telegram\Parser\Contracts\TelegramParserExportBuilderInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TelegramParserController extends BaseTelegramController
{
    use HandlesParserDownloads;

    private const SUPPORTED_LOCALES = ['ru', 'en'];

    public function __construct(
        private readonly TelegramParserApplicationServiceInterface $parserApplicationService,
        private readonly TelegramParserExportBuilderInterface $exportBuilder,
        private readonly ExcelWorkbookServiceInterface $excelWorkbookService,
    ) {
    }

    public function start(TelegramParserStartRequest $request): JsonResponse
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
        $this->applyRequestLocale($this->resolveRequestLocale($request));

        $payload = $this->parserApplicationService->getDownloadPayload($this->userId($request), $runId);
        $filename = $this->buildExportFilename(
            'telegram-parser',
            (string) ($payload['chatUsername'] ?? 'chat'),
            'xlsx'
        );

        return $this->excelWorkbookService->download($filename, $this->exportBuilder->buildSheets($payload));
    }

    public function downloadJson(Request $request, string $runId): StreamedResponse
    {
        $this->applyRequestLocale($this->resolveRequestLocale($request));

        $payload = $this->parserApplicationService->getDownloadPayload($this->userId($request), $runId);
        $filename = $this->buildExportFilename(
            'telegram-parser',
            (string) ($payload['chatUsername'] ?? 'chat'),
            'json'
        );

        return $this->streamJsonDownload($payload, $filename);
    }

    private function resolveRequestLocale(Request $request): string
    {
        $locale = strtolower(trim((string) $request->query('locale', app()->getLocale())));

        return in_array($locale, self::SUPPORTED_LOCALES, true) ? $locale : 'en';
    }
}
