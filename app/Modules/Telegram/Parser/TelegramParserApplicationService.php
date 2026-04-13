<?php

namespace App\Modules\Telegram\Parser;

use App\Modules\Telegram\DTO\Request\TelegramParserStartDTO;
use App\Modules\Telegram\DTO\Result\ParserRunStatusDTO;
use App\Modules\Telegram\Parser\Contracts\TelegramParserApplicationServiceInterface;

class TelegramParserApplicationService implements TelegramParserApplicationServiceInterface
{
    public function __construct(
        private readonly TelegramParserRunStore $runStore,
        private readonly TelegramParserCollector $collector,
        private readonly ParserRunGuard $runGuard,
    ) {
    }

    public function start(TelegramParserStartDTO $input): ParserRunStatusDTO
    {
        $run = $this->runStore->create($input->userId, $input->toContext());

        return $this->presentRun($run);
    }

    public function status(int $userId, string $runId): ?ParserRunStatusDTO
    {
        $run = $this->runStore->mutate($userId, $runId, function (array $state): array {
            if (($state['status'] ?? null) !== 'running') {
                return $state;
            }

            $cursor = is_array($state['cursor'] ?? null) ? $state['cursor'] : [];
            $nowTs = now()->timestamp;
            $nextAdvanceAt = (int) ($cursor['nextAdvanceAt'] ?? 0);

            if ($nextAdvanceAt > $nowTs) {
                return $state;
            }

            $state = $this->collector->advance($state);

            if (($state['status'] ?? null) === 'running') {
                $cursor = is_array($state['cursor'] ?? null) ? $state['cursor'] : [];
                $cursor['nextAdvanceAt'] = $nowTs + 2;
                $state['cursor'] = $cursor;
            }

            return $state;
        });

        return is_array($run) ? $this->presentRun($run) : null;
    }

    public function stop(int $userId, string $runId): ?ParserRunStatusDTO
    {
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

        return is_array($run) ? $this->presentRun($run) : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDownloadPayload(int $userId, string $runId): array
    {
        $run = $this->runGuard->requireExistingRun($this->runStore->get($userId, $runId));

        return $this->runGuard->requireDownloadablePayload($run);
    }

    /**
     * @param array<string, mixed> $run
     */
    private function presentRun(array $run): ParserRunStatusDTO
    {
        $stats = is_array($run['stats'] ?? null) ? $run['stats'] : [];
        $status = (string) ($run['status'] ?? 'running');
        $runId = (string) ($run['runId'] ?? '');
        $hasResult = is_array($run['result'] ?? null);

        return new ParserRunStatusDTO([
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
        ]);
    }
}

