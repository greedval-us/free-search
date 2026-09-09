<?php

namespace App\Modules\TelegramBot\Application\Actions;

use App\Modules\TelegramBot\Application\ArtifactRegistry;
use App\Modules\TelegramBot\Domain\Contracts\BotAction;
use App\Modules\TelegramBot\Domain\DTO\BotButton;
use App\Modules\TelegramBot\Domain\DTO\BotContext;
use App\Modules\TelegramBot\Domain\DTO\BotScreen;

final readonly class FilesAction implements BotAction
{
    public function __construct(private ArtifactRegistry $artifacts, private MenuAction $menu) {}

    public function key(): string
    {
        return 'files';
    }

    public function handle(BotContext $context, array $parameters): BotScreen
    {
        if ($context->userId === null) {
            return $this->menu->handle($context, []);
        }
        $provider = $this->artifacts->get($parameters['k'] ?? 'parser');
        $page = max(1, min((int) config('telegram_bot.max_page'), (int) ($parameters['page'] ?? 1)));
        $records = $provider->listing($context->userId, $page);
        $buttons = [];
        foreach ($records->items() as $record) {
            foreach ($record['formats'] as $format) {
                $buttons[] = new BotButton($record['label'].' / '.strtoupper($format), 'action', 'send',
                    ['k' => $provider->key(), 'i' => $record['id'], 'f' => $format]);
            }
        }
        if ($page > 1) {
            $buttons[] = new BotButton(__('telegram_bot.menu.previous', [], $context->locale), 'action', 'files', ['k' => $provider->key(), 'page' => $page - 1]);
        }
        if ($records->hasMorePages()) {
            $buttons[] = new BotButton(__('telegram_bot.menu.next', [], $context->locale), 'action', 'files', ['k' => $provider->key(), 'page' => $page + 1]);
        }
        $buttons[] = new BotButton(__('telegram_bot.menu.back', [], $context->locale), 'action', 'menu');

        return new BotScreen(__('telegram_bot.screens.'.($records->isEmpty() ? 'empty' : 'files'), [], $context->locale), $buttons);
    }
}
