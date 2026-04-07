<?php

namespace App\Modules\Telegram\Actions;

use App\Facades\MadelineProto;
use Illuminate\Support\Facades\Log;

abstract class AbstractTelegramAction
{
    protected function madeline(): \danog\MadelineProto\API
    {
        return MadelineProto::getFacadeRoot();
    }

    protected function logContext(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $method = $trace[1]['function'] ?? '';
        return static::class . '::' . $method;
    }

    protected function logError(\Throwable $e, array $data = []): void
    {
        Log::error(
            message: "[" . $this->logContext() . "] " . $e->getMessage(),
            context: array_merge(['trace' => $e->getTraceAsString()], $data)
        );
    }

    protected function logInfo(string $message, array $data = []): void
    {
        Log::info("[" . $this->logContext() . "] $message", $data);
    }

    protected function logDebug(string $message, array $data = []): void
    {
        Log::debug("[" . $this->logContext() . "] $message", $data);
    }

    protected function logWarning(string $message, array $data = []): void
    {
        Log::warning("[" . $this->logContext() . "] $message", $data);
    }
}
