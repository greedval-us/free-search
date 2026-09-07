<?php

namespace App\Providers;

use App\Http\Responses\Contracts\TelegramMediaResponderInterface;
use App\Http\Responses\TelegramMediaResponder;
use App\Support\Providers\BindingsServiceProvider;

final class HttpServiceProvider extends BindingsServiceProvider
{
    protected function bindings(): array
    {
        return [
            TelegramMediaResponderInterface::class => TelegramMediaResponder::class,
        ];
    }
}
