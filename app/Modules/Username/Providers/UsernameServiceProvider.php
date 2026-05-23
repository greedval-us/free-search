<?php

namespace App\Modules\Username\Providers;

use App\Modules\Username\Application\Contracts\UsernameSearchServiceInterface;
use App\Modules\Username\Application\Services\UsernameSearchService;
use App\Modules\Username\Domain\Contracts\UsernameSourceCheckerInterface;
use App\Modules\Username\Infrastructure\Http\UsernameSourceHttpChecker;
use App\Support\Providers\BindingsServiceProvider;

final class UsernameServiceProvider extends BindingsServiceProvider
{
    protected function bindings(): array
    {
        return [
            UsernameSearchServiceInterface::class => UsernameSearchService::class,
            UsernameSourceCheckerInterface::class => UsernameSourceHttpChecker::class,
        ];
    }
}
