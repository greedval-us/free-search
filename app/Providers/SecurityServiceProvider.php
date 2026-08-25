<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

final class SecurityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureRateLimiters();
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('registration', fn (Request $request): array => $this->ipLimits(
            $request,
            'registration',
        ));

        RateLimiter::for('password-reset', function (Request $request): array {
            $email = Str::lower(trim((string) $request->input('email')));

            return [
                ...$this->ipLimits($request, 'password_reset'),
                Limit::perMinute((int) config('security.rate_limits.password_reset.per_email_per_minute', 3))
                    ->by("password-reset:email:{$email}"),
            ];
        });

        RateLimiter::for('resend-webhook', fn (Request $request): Limit => Limit::perMinute(
            (int) config('security.rate_limits.resend_webhook.per_minute', 60),
        )->by("resend-webhook:{$request->ip()}"));
    }

    /**
     * @return list<Limit>
     */
    private function ipLimits(Request $request, string $configKey): array
    {
        $ip = $request->ip();

        return [
            Limit::perMinute((int) config("security.rate_limits.{$configKey}.per_minute", 5))
                ->by("{$configKey}:minute:{$ip}"),
            Limit::perHour((int) config("security.rate_limits.{$configKey}.per_hour", 20))
                ->by("{$configKey}:hour:{$ip}"),
        ];
    }
}
