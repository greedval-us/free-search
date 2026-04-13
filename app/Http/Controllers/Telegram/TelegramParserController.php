<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Http\Requests\Telegram\TelegramParserStartRequest;
use App\Modules\Export\Excel\ExcelWorkbookService;
use App\Modules\Telegram\Parser\TelegramParserExportBuilder;
use App\Modules\Telegram\Parser\TelegramParserApplicationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TelegramParserController extends Controller
{
    public function __construct(
        private readonly TelegramParserApplicationService $parserApplicationService,
        private readonly TelegramParserExportBuilder $exportBuilder,
        private readonly ExcelWorkbookService $excelWorkbookService,
    ) {
    }

    public function start(TelegramParserStartRequest $request): JsonResponse
    {
        return response()->json($this->parserApplicationService->start($request->toStartDTO()));
    }

    public function status(Request $request, string $runId): JsonResponse
    {
        $run = $this->parserApplicationService->status((int) $request->user()->id, $runId);
        abort_unless($run !== null, 404);

        return response()->json($run);
    }

    public function stop(Request $request, string $runId): JsonResponse
    {
        $run = $this->parserApplicationService->stop((int) $request->user()->id, $runId);
        abort_unless($run !== null, 404);

        return response()->json($run);
    }

    public function downloadExcel(Request $request, string $runId): BinaryFileResponse
    {
        $run = $this->parserApplicationService->getRun((int) $request->user()->id, $runId);
        abort_unless($run !== null, 404);
        abort_unless(in_array(($run['status'] ?? null), ['completed', 'stopped'], true), 409);

        $payload = is_array($run['result'] ?? null) ? $run['result'] : null;
        abort_unless($payload !== null, 404);
        $chatUsername = (string) ($payload['chatUsername'] ?? 'chat');

        $filename = sprintf(
            'telegram-parser-%s-%s.xlsx',
            preg_replace('/[^a-z0-9_-]+/i', '-', $chatUsername) ?: 'chat',
            Carbon::now(config('app.timezone'))->format('Ymd-His')
        );

        return $this->excelWorkbookService->download($filename, $this->exportBuilder->buildSheets($payload));
    }

    public function downloadJson(Request $request, string $runId): StreamedResponse
    {
        $run = $this->parserApplicationService->getRun((int) $request->user()->id, $runId);
        abort_unless($run !== null, 404);
        abort_unless(in_array(($run['status'] ?? null), ['completed', 'stopped'], true), 409);

        $payload = is_array($run['result'] ?? null) ? $run['result'] : null;
        abort_unless($payload !== null, 404);
        $chatUsername = (string) ($payload['chatUsername'] ?? 'chat');

        $filename = sprintf(
            'telegram-parser-%s-%s.json',
            preg_replace('/[^a-z0-9_-]+/i', '-', $chatUsername) ?: 'chat',
            Carbon::now(config('app.timezone'))->format('Ymd-His')
        );

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
}

