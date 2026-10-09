<?php

namespace App\Integrations\TelegramBot;

use App\Modules\TelegramBot\Application\DeliveryOutbox;
use App\Modules\TelegramBot\Domain\Contracts\ScheduledReportArtifactProvider;
use App\Modules\TelegramBot\Models\BotLink;
use App\Modules\TelegramBot\Support\BotConfig;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class CompletedReportBotDispatcher
{
    public function __construct(private BotConfig $config, private DeliveryOutbox $outbox) {}

    public function dispatch(ScheduledReportArtifactProvider $provider, int $reportId): void
    {
        if (! $this->config->active()) {
            return;
        }

        try {
            $userId = $provider->automaticRecipient($reportId);
            $link = $userId === null ? null : BotLink::query()->where('user_id', $userId)->first();
            if ($link !== null) {
                $this->outbox->enqueue($link, $provider->key(), (string) $reportId, ['format' => 'html']);
            }
        } catch (Throwable $exception) {
            // A completed website report survives failure of this optional delivery channel.
            Log::warning('Scheduled report bot delivery unavailable.', ['kind' => $provider->key(), 'report_id' => $reportId, 'exception_class' => $exception::class]);
            throw $exception;
        }
    }
}
