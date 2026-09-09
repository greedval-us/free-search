<?php

namespace App\Modules\TelegramBot\Application;

use App\Modules\TelegramBot\Domain\Contracts\BotAction;
use App\Modules\TelegramBot\Domain\DTO\BotContext;
use App\Modules\TelegramBot\Domain\DTO\BotScreen;

final class BotRouter
{
    /** @var array<string, BotAction> */
    private array $actions = [];

    /** @param iterable<BotAction> $actions */
    public function __construct(iterable $actions)
    {
        foreach ($actions as $action) {
            if (isset($this->actions[$action->key()])) {
                throw new \LogicException('Duplicate bot action.');
            }
            $this->actions[$action->key()] = $action;
        }
    }

    /** @param array<string, string> $parameters */
    public function dispatch(string $key, BotContext $context, array $parameters = []): BotScreen
    {
        // Callback data selects a registered action, never an arbitrary PHP method.
        return ($this->actions[$key] ?? $this->actions['menu'])->handle($context, $parameters);
    }
}
