<?php

namespace App\Modules\Username\Providers;

use App\Modules\Username\Domain\Contracts\UsernameSourceCheckerInterface;
use App\Modules\Username\Infrastructure\Http\UsernameSourceHttpChecker;
use Illuminate\Support\ServiceProvider;

final class UsernameServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UsernameSourceCheckerInterface::class, UsernameSourceHttpChecker::class);
    }
}

