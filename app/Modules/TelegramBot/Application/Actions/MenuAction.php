<?php

namespace App\Modules\TelegramBot\Application\Actions;

use App\Modules\TelegramBot\Domain\Contracts\BotAction;
use App\Modules\TelegramBot\Domain\DTO\BotButton;
use App\Modules\TelegramBot\Domain\DTO\BotContext;
use App\Modules\TelegramBot\Domain\DTO\BotScreen;
use App\Modules\TelegramBot\Support\BotConfig;

final readonly class MenuAction implements BotAction
{
    public function __construct(private BotConfig $config) {}

    public function key(): string
    {
        return 'menu';
    }

    public function handle(BotContext $context, array $parameters): BotScreen
    {
        $menus = $this->config->get('menus', []);
        $page = isset($menus[$parameters['p'] ?? 'main']) ? ($parameters['p'] ?? 'main') : 'main';
        $buttons = [];
        foreach ($menus[$page] as $item) {
            if (($item['linked'] ?? false) && $context->userId === null) {
                continue;
            }
            $label = __('telegram_bot.'.$item['label'], [], $context->locale);
            if (isset($item['action'])) {
                $buttons[] = new BotButton($label, 'action', $item['action'], $item['parameters'] ?? []);
            } else {
                $url = $this->config->siteUrl($item['path']);
                $webapp = ($item['webapp'] ?? false) && parse_url($url, PHP_URL_SCHEME) === 'https';
                $buttons[] = new BotButton($label, $webapp ? 'webapp' : 'url', $url);
            }
        }
        $textKey = $page === 'main' && $context->userId === null ? 'unlinked' : $page;

        return new BotScreen(__('telegram_bot.screens.'.$textKey, [], $context->locale), $buttons);
    }
}
