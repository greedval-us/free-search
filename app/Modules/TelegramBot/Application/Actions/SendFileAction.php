<?php

namespace App\Modules\TelegramBot\Application\Actions;

use App\Modules\TelegramBot\Application\ArtifactRegistry;
use App\Modules\TelegramBot\Application\DeliveryOutbox;
use App\Modules\TelegramBot\Domain\Contracts\BotAction;
use App\Modules\TelegramBot\Domain\DTO\BotContext;
use App\Modules\TelegramBot\Domain\DTO\BotScreen;
use App\Modules\TelegramBot\Models\BotLink;

final readonly class SendFileAction implements BotAction
{
    public function __construct(private DeliveryOutbox $outbox, private ArtifactRegistry $artifacts, private MenuAction $menu) {}

    public function key(): string
    {
        return 'send';
    }

    public function handle(BotContext $context, array $parameters): BotScreen
    {
        $link = $context->linkId === null ? null : BotLink::query()->find($context->linkId);
        if ($link === null) {
            return $this->menu->handle($context, []);
        }
        $provider = $this->artifacts->get($parameters['k'] ?? '');
        $accepted = $this->outbox->enqueue($link, $provider->key(), (string) (int) ($parameters['i'] ?? 0),
            ['format' => $parameters['f'] ?? ''], false, $context->requestId);

        return new BotScreen(__('telegram_bot.screens.'.($accepted ? 'queued' : 'unlinked'), [], $context->locale));
    }
}
