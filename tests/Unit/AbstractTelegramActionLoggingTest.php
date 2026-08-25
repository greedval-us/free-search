<?php

namespace Tests\Unit;

use App\Modules\Telegram\Actions\AbstractTelegramAction;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AbstractTelegramActionLoggingTest extends TestCase
{
    public function test_error_logging_uses_the_log_method_and_masks_sensitive_data(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return str_ends_with($message, '::logError] Telegram action failed')
                    && ! str_contains($message, 'secret-value')
                    && $context['exception'] === \RuntimeException::class
                    && $context['error_code'] === 17
                    && $context['payload']['query'] === '***'
                    && $context['payload']['limit'] === 10;
            });

        $action = new class extends AbstractTelegramAction
        {
            public function report(\Throwable $exception, array $context): void
            {
                $this->logError($exception, $context);
            }
        };

        $action->report(
            new \RuntimeException('secret-value', 17),
            ['query' => 'private search', 'limit' => 10],
        );

    }
}
