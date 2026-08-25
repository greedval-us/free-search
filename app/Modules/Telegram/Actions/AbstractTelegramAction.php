<?php

namespace App\Modules\Telegram\Actions;

use App\Facades\MadelineProto;
use App\Support\Activity\RequestPayloadSanitizer;
use App\Support\MadelineProto\MadelineProtoManager;
use danog\MadelineProto\API;
use Illuminate\Support\Facades\Log;

abstract class AbstractTelegramAction
{
    private const DEFAULT_MAX_RETRIES = 3;

    private const BASE_RETRY_DELAY_MS = 500;

    protected function madeline(): API
    {
        /** @var MadelineProtoManager $manager */
        $manager = MadelineProto::getFacadeRoot();

        return $manager->client();
    }

    protected function logContext(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $method = $trace[2]['function'] ?? '';

        return static::class.'::'.$method;
    }

    protected function logError(\Throwable $e, array $data = []): void
    {
        Log::error(
            message: $this->prefixedMessage('Telegram action failed'),
            context: [
                'exception' => $e::class,
                'error_code' => $e->getCode(),
                'payload' => $this->sanitizeContext($data),
            ]
        );
    }

    protected function logInfo(string $message, array $data = []): void
    {
        Log::info($this->prefixedMessage($message), $this->sanitizeContext($data));
    }

    protected function logDebug(string $message, array $data = []): void
    {
        Log::debug($this->prefixedMessage($message), $this->sanitizeContext($data));
    }

    protected function logWarning(string $message, array $data = []): void
    {
        Log::warning($this->prefixedMessage($message), $this->sanitizeContext($data));
    }

    protected function executeWithRetry(
        callable $callback,
        array $context = [],
        int $maxRetries = self::DEFAULT_MAX_RETRIES
    ): mixed {
        $attempt = 0;

        while (true) {
            try {
                return $callback();
            } catch (\Throwable $e) {
                $attempt++;
                $floodWaitSeconds = $this->extractFloodWaitSeconds($e);

                if ($floodWaitSeconds !== null && $attempt <= $maxRetries) {
                    $delayMs = max(1, $floodWaitSeconds) * 1000;
                    $this->logWarning(
                        message: 'Flood wait detected, retrying after delay',
                        data: array_merge($context, [
                            'attempt' => $attempt,
                            'retry_in_ms' => $delayMs,
                        ])
                    );
                    usleep($delayMs * 1000);

                    continue;
                }

                if ($this->isRetryable($e) && $attempt <= $maxRetries) {
                    $delayMs = self::BASE_RETRY_DELAY_MS * (2 ** ($attempt - 1));
                    $this->logWarning(
                        message: 'Transient Telegram API error, retrying',
                        data: array_merge($context, [
                            'attempt' => $attempt,
                            'retry_in_ms' => $delayMs,
                        ])
                    );
                    usleep($delayMs * 1000);

                    continue;
                }

                throw $e;
            }
        }
    }

    protected function extractFloodWaitSeconds(\Throwable $e): ?int
    {
        $message = $e->getMessage();

        if (preg_match('/FLOOD_WAIT_(\d+)/i', $message, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/A wait of (\d+) seconds is required/i', $message, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    protected function isRetryable(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());
        $retryableFragments = [
            'timeout',
            'temporarily unavailable',
            'connection reset',
            'connection refused',
            'network',
            'server error',
            'internal error',
        ];

        foreach ($retryableFragments as $fragment) {
            if (str_contains($message, $fragment)) {
                return true;
            }
        }

        return false;
    }

    protected function sanitizeContext(array $data): array
    {
        return (new RequestPayloadSanitizer([
            'api_hash',
            'api_id',
            'phone',
            'phone_number',
            'access_hash',
            'from_id',
            'message',
            'peer',
            'q',
            'query',
            'saved_peer_id',
            'text',
        ]))->sanitize($data);
    }

    private function prefixedMessage(string $message): string
    {
        return sprintf('[%s] %s', $this->logContext(), $message);
    }
}
