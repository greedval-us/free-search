<?php

namespace App\Providers;

use App\Support\Observability\MoonShineLoginAlertService;
use App\Support\Observability\MoonShineLoginContext;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureMoonShineLoginAlerts();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Configure audit and alerts for MoonShine admin logins.
     */
    protected function configureMoonShineLoginAlerts(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            if ($event->guard !== 'moonshine') {
                return;
            }

            if (!is_object($event->user)) {
                return;
            }

            /** @var Request|null $request */
            $request = app('request');
            $ip = (string) ($request?->ip() ?? 'unknown');
            $agent = Str::limit((string) ($request?->userAgent() ?? 'unknown'), 255);
            $admin = method_exists($event->user, 'getAuthIdentifier')
                ? (string) $event->user->getAuthIdentifier()
                : 'unknown';
            $email = property_exists($event->user, 'email') ? (string) ($event->user->email ?? '') : '';

            $context = new MoonShineLoginContext(
                adminId: $admin,
                adminEmail: $email,
                ip: $ip,
                userAgent: $agent,
                guard: $event->guard,
                timestampIso: now()->toIso8601String(),
            );

            app(MoonShineLoginAlertService::class)->handle($context);
        });
    }
}
