<?php

namespace App\Modules\TelegramBot\Console;

use App\Modules\TelegramBot\Application\DeliveryOutbox;
use App\Modules\TelegramBot\Models\BotLink;
use App\Modules\TelegramBot\Support\BotConfig;
use App\Modules\TelegramBot\Support\TelegramLimits;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class BroadcastBotMessage extends Command
{
    protected $signature = 'telegram-bot:broadcast {message} {--locale=} {--dry-run} {--confirm : Explicitly queue the broadcast}';

    protected $description = 'Queue an announcement only for linked users who opted into broadcasts';

    public function handle(BotConfig $config, DeliveryOutbox $outbox): int
    {
        $message = trim((string) $this->argument('message'));
        $locale = $this->option('locale');
        if (! $config->active() || $message === '' || mb_strlen($message) > TelegramLimits::MESSAGE_CHARACTERS || ($locale !== null && ! in_array($locale, ['ru', 'en'], true))) {
            $this->error('Enable the bot and provide 1-'.TelegramLimits::MESSAGE_CHARACTERS.' characters; locale must be ru or en.');

            return self::FAILURE;
        }
        $query = BotLink::query()->where('broadcasts_enabled', true)
            ->when($locale !== null, fn ($query) => $query->where('locale', $locale));
        if ($this->option('dry-run')) {
            $this->info('Opted-in links: '.$query->count().'. Access will be rechecked on delivery. Nothing queued.');

            return self::SUCCESS;
        }
        if (! $this->option('confirm')) {
            $this->error('Use --dry-run to inspect recipients, then --confirm to queue the announcement.');

            return self::FAILURE;
        }
        $campaign = (string) Str::uuid();
        $count = 0;
        $query->chunkById($config->integer('batch_size'), function ($links) use ($outbox, $message, $campaign, &$count): void {
            foreach ($links as $link) {
                $count += (int) $outbox->enqueue($link, 'broadcast', $campaign, ['message' => $message]);
            }
        });
        $this->info('Campaign '.$campaign.': queued '.$count.' deliveries.');

        return self::SUCCESS;
    }
}
