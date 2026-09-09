<?php

namespace App\Modules\TelegramBot\Console;

use App\Modules\TelegramBot\Application\DeliveryOutbox;
use App\Modules\TelegramBot\Models\BotDelivery;
use App\Modules\TelegramBot\Models\BotReport;
use App\Modules\TelegramBot\Models\LinkRequest;
use App\Modules\TelegramBot\Support\BotConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

final class MaintainBotDeliveries extends Command
{
    protected $signature = 'telegram-bot:maintain {--prune : Remove expired metadata and abandoned temporary files}';

    protected $description = 'Retry pending bot deliveries; optionally prune module-owned temporary data';

    public function handle(BotConfig $config, DeliveryOutbox $outbox): int
    {
        if ($this->option('prune')) {
            LinkRequest::query()->where('expires_at', '<=', now())->delete();
            BotReport::query()->where('expires_at', '<=', now())->delete();
            BotDelivery::query()->where('created_at', '<', now()->subDays($config->integer('delivery_retention_days')))->delete();
            $disk = Storage::disk('local');
            $cutoff = now()->subHours($config->integer('temporary_retention_hours'))->timestamp;
            foreach ($disk->files($config->get('temporary_directory')) as $path) {
                if ($disk->lastModified($path) < $cutoff) {
                    $disk->delete($path);
                }
            }
        }
        $outbox->retryPending();

        return self::SUCCESS;
    }
}
