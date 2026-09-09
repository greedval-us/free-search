<?php

namespace App\Modules\TelegramBot\Jobs;

use App\Modules\TelegramBot\Application\BotUpdateProcessor;
use App\Modules\TelegramBot\Domain\DTO\BotUpdate;
use App\Modules\TelegramBot\Support\BotConfig;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Support\Facades\Cache;

final class ProcessBotUpdate extends BotJob implements ShouldBeEncrypted
{
    public function __construct(public int $botId, public BotUpdate $update)
    {
        parent::__construct($update->telegramId);
    }

    public function handle(BotUpdateProcessor $processor, BotConfig $config): void
    {
        if (! $config->active() || $this->botId !== $config->botId()) {
            return;
        }
        $key = 'telegram-bot:update:'.$this->botId.':'.$this->update->id;
        if (! Cache::has($key)) {
            $processor->process($this->update);
            Cache::put($key, true, $config->integer('update_retention_seconds'));
        }
    }
}
