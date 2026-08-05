<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Notifications\UserNotificationService;
use App\Support\Observability\MoonShineLoginAlertService;
use App\Support\Observability\MoonShineLoginContext;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
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
        $this->configureAuthMail();
        $this->configureMoonShineLoginAlerts();
        $this->configureUserLoginNotifications();
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
     * Configure branded authentication emails.
     */
    protected function configureAuthMail(): void
    {
        VerifyEmail::toMailUsing(function (object $notifiable, string $url): MailMessage {
            return (new MailMessage)
                ->subject(__('mail.verify_email.subject'))
                ->view('emails.verify-email', [
                    'actionUrl' => $url,
                    'appName' => config('app.name', 'Uraboros'),
                    'appUrl' => config('app.url'),
                    'preheader' => __('mail.verify_email.preheader'),
                    'title' => __('mail.verify_email.title'),
                    'intro' => __('mail.verify_email.intro'),
                    'buttonText' => __('mail.verify_email.button'),
                    'expiry' => __('mail.verify_email.expiry'),
                    'fallback' => __('mail.verify_email.fallback'),
                    'security' => __('mail.verify_email.security'),
                    'ignore' => __('mail.verify_email.ignore'),
                    'signature' => __('mail.verify_email.signature'),
                ]);
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject(__('mail.reset_password.subject'))
                ->view('emails.reset-password', [
                    'actionUrl' => $url,
                    'appName' => config('app.name', 'Uraboros'),
                    'appUrl' => config('app.url'),
                    'preheader' => __('mail.reset_password.preheader'),
                    'title' => __('mail.reset_password.title'),
                    'intro' => __('mail.reset_password.intro'),
                    'buttonText' => __('mail.reset_password.button'),
                    'expiry' => __('mail.reset_password.expiry', [
                        'count' => config('auth.passwords.'.config('fortify.passwords', 'users').'.expire'),
                    ]),
                    'fallback' => __('mail.reset_password.fallback'),
                    'security' => __('mail.reset_password.security'),
                    'ignore' => __('mail.reset_password.ignore'),
                    'signature' => __('mail.reset_password.signature'),
                ]);
        });
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

    protected function configureUserLoginNotifications(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            if ($event->guard !== 'web') {
                return;
            }

            if (! $event->user instanceof User) {
                return;
            }

            /** @var Request|null $request */
            $request = app('request');

            app(UserNotificationService::class)->sendLoginGreeting(
                user: $event->user,
                ip: (string) ($request?->ip() ?? 'unknown'),
                userAgent: Str::limit((string) ($request?->userAgent() ?? 'unknown'), 255),
                occurredAt: now(),
            );

            app(UserNotificationService::class)->sendNewIpLoginAlert(
                user: $event->user,
                ip: (string) ($request?->ip() ?? 'unknown'),
                userAgent: Str::limit((string) ($request?->userAgent() ?? 'unknown'), 255),
                occurredAt: now(),
            );
        });
    }
}
