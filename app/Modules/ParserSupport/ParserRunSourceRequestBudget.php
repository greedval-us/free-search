<?php

namespace App\Modules\ParserSupport;

use Illuminate\Support\Facades\DB;

final class ParserRunSourceRequestBudget
{
    /** @var array{module: string, user_id: int, run_id: string}|null */
    private ?array $run = null;

    private bool $exhausted = false;

    public function __construct(private readonly ParserRunConfig $config) {}

    public function duringRun(string $module, int $userId, string $runId, callable $callback): mixed
    {
        $previousRun = $this->run;
        $previousExhausted = $this->exhausted;
        $this->run = ['module' => $module, 'user_id' => $userId, 'run_id' => $runId];
        $this->exhausted = false;

        try {
            $result = $callback();
            if ($this->exhausted) {
                throw new ParserRunSourceRequestBudgetExceeded;
            }

            return $result;
        } catch (\Throwable $exception) {
            // Some Telegram actions deliberately turn source errors into empty
            // results. A swallowed refusal must still terminate this run.
            if ($this->exhausted && ! $exception instanceof ParserRunSourceRequestBudgetExceeded) {
                throw new ParserRunSourceRequestBudgetExceeded($exception);
            }

            throw $exception;
        } finally {
            $this->run = $previousRun;
            $this->exhausted = $previousExhausted;
        }
    }

    public function charge(): void
    {
        if ($this->run === null) {
            return;
        }

        $reserved = ! $this->exhausted && DB::table('parser_runs')
            ->where($this->run)
            ->where('status', 'running')
            ->where(static fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where('source_request_count', '<', $this->config->maxSourceRequests())
            ->increment('source_request_count');

        if (! $reserved) {
            $this->exhausted = true;
            throw new ParserRunSourceRequestBudgetExceeded;
        }
    }
}
