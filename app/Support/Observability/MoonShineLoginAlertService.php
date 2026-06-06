<?php

declare(strict_types=1);

namespace App\Support\Observability;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class MoonShineLoginAlertService
{
    public function handle(MoonShineLoginContext $context): void
    {
        $this->writeAuditLog($context);
        $this->sendEmailIfEnabled($context);
    }

    private function writeAuditLog(MoonShineLoginContext $context): void
    {
        Log::channel((string) config('moonshine.alerts.login_channel', 'stack'))->warning('MoonShine admin login', [
            'admin_id' => $context->adminId,
            'admin_email' => $context->adminEmail !== '' ? $context->adminEmail : null,
            'ip' => $context->ip,
            'user_agent' => $context->userAgent,
            'guard' => $context->guard,
            'at' => $context->timestampIso,
        ]);
    }

    private function sendEmailIfEnabled(MoonShineLoginContext $context): void
    {
        $notify = (bool) config('moonshine.alerts.login_email_enabled', false);
        $target = trim((string) config('moonshine.alerts.login_email', ''));
        if (!$notify || $target === '') {
            return;
        }

        try {
            Mail::raw(
                "MoonShine login detected\nAdmin: {$context->adminEmail}\nIP: {$context->ip}\nUser-Agent: {$context->userAgent}\nTime: {$context->timestampIso}",
                static function ($message) use ($target): void {
                    $message->to($target)->subject('MoonShine Admin Login Alert');
                }
            );
        } catch (Throwable $e) {
            Log::warning('MoonShine login alert email failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
