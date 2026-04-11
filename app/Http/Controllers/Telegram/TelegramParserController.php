<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Http\Requests\Telegram\TelegramParserStartRequest;
use App\Modules\Export\Excel\ExcelWorkbookService;
use App\Modules\Telegram\Parser\TelegramParserCollector;
use App\Modules\Telegram\Parser\TelegramParserExportBuilder;
use App\Modules\Telegram\Parser\TelegramParserRunStore;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TelegramParserController extends Controller
{
    public function __construct(
        private readonly TelegramParserRunStore $runStore,
        private readonly TelegramParserCollector $collector,
        private readonly TelegramParserExportBuilder $exportBuilder,
        private readonly ExcelWorkbookService $excelWorkbookService,
    ) {
    }

    public function start(TelegramParserStartRequest $request): JsonResponse
    {
        $run = $this->runStore->create((int) $request->user()->id, [
            'chatUsername' => $request->chatUsername(),
            'period' => $request->period(),
            'keyword' => $request->keyword(),
            'range' => $request->range(),
        ]);

        return response()->json($this->presentRun($run));
    }

    public function status(Request $request, string $runId): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $run = $this->runStore->mutate($userId, $runId, fn (array $state): array => $this->collector->advance($state));
        abort_unless($run !== null, 404);

        return response()->json($this->presentRun($run));
    }

    public function stop(Request $request, string $runId): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $run = $this->runStore->mutate($userId, $runId, function (array $state): array {
            if (($state['status'] ?? null) === 'completed') {
                return $state;
            }

            if (!is_array($state['result'] ?? null)) {
                $state['result'] = $this->collector->buildResultSnapshot($state);
            }

            $state['status'] = 'stopped';
            $state['stage'] = 'stopped';
            $state['error'] = null;

            return $state;
        });
        abort_unless($run !== null, 404);

        return response()->json($this->presentRun($run));
    }

    public function downloadExcel(Request $request, string $runId): BinaryFileResponse
    {
        $run = $this->runStore->get((int) $request->user()->id, $runId);
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
        $run = $this->runStore->get((int) $request->user()->id, $runId);
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

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    private function presentRun(array $run): array
    {
        $stats = is_array($run['stats'] ?? null) ? $run['stats'] : [];
        $status = (string) ($run['status'] ?? 'running');
        $runId = (string) ($run['runId'] ?? '');
        $hasResult = is_array($run['result'] ?? null);

        return [
            'ok' => true,
            'runId' => $runId,
            'status' => $status,
            'stage' => (string) ($run['stage'] ?? 'idle'),
            'progress' => (int) ($run['progress'] ?? 0),
            'processedMessages' => (int) ($stats['processedMessages'] ?? 0),
            'processedComments' => (int) ($stats['processedComments'] ?? 0),
            'error' => $run['error'] ?? null,
            'downloadUrl' => in_array($status, ['completed', 'stopped'], true) && $hasResult && $runId !== ''
                ? route('telegram.parser.download-excel', ['runId' => $runId])
                : null,
            'downloadJsonUrl' => in_array($status, ['completed', 'stopped'], true) && $hasResult && $runId !== ''
                ? route('telegram.parser.download-json', ['runId' => $runId])
                : null,
        ];
    }
}
