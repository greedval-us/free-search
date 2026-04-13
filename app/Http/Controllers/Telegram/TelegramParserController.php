<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Requests\Telegram\TelegramParserStartRequest;
use App\Modules\Export\Excel\ExcelWorkbookService;
use App\Modules\Telegram\Parser\TelegramParserExportBuilder;
use App\Modules\Telegram\Parser\Contracts\TelegramParserApplicationServiceInterface;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TelegramParserController extends BaseTelegramController
{
    public function __construct(
        private readonly TelegramParserApplicationServiceInterface $parserApplicationService,
        private readonly TelegramParserExportBuilder $exportBuilder,
        private readonly ExcelWorkbookService $excelWorkbookService,
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
        $filename = $this->buildExportFilename('telegram-parser', (string) ($payload['chatUsername'] ?? 'chat'), 'xlsx');

        return $this->excelWorkbookService->download($filename, $this->exportBuilder->buildSheets($payload));
    }

    public function downloadJson(Request $request, string $runId): StreamedResponse
    {
        $payload = $this->parserApplicationService->getDownloadPayload($this->userId($request), $runId);
        $filename = $this->buildExportFilename('telegram-parser', (string) ($payload['chatUsername'] ?? 'chat'), 'json');

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

    private function buildExportFilename(string $prefix, string $chatUsername, string $extension): string
    {
        return sprintf(
            '%s-%s-%s.%s',
            $prefix,
            preg_replace('/[^a-z0-9_-]+/i', '-', $chatUsername) ?: 'chat',
            Carbon::now(config('app.timezone'))->format('Ymd-His'),
            ltrim($extension, '.')
        );
    }
}

