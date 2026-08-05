<?php

namespace App\Support\Notifications;

use App\Models\User;
use App\Models\UserSubscription;
use App\Notifications\SystemDatabaseNotification;
use Carbon\CarbonInterface;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

final class UserNotificationService
{
    public function sendWelcome(User $user): void
    {
        $this->sendUniqueWithinWindow(
            user: $user,
            fingerprint: 'welcome',
            payload: [
                'title_key' => 'systemNotifications.welcome.title',
                'body_key' => 'systemNotifications.welcome.body',
                'url' => '/dashboard',
                'kind' => 'welcome',
            ],
            hours: 24 * 30,
        );
    }

    public function sendLoginGreeting(
        User $user,
        string $ip,
        string $userAgent,
        CarbonInterface $occurredAt,
    ): void {
        $this->sendUniqueWithinWindow(
            user: $user,
            fingerprint: 'login:'.$occurredAt->format('Y-m-d'),
            payload: [
                'title_key' => 'systemNotifications.loginGreeting.title',
                'body_key' => 'systemNotifications.loginGreeting.body',
                'body_params' => [
                    'date' => $occurredAt->format('d.m.Y H:i'),
                    'ip' => $ip,
                ],
                'url' => '/dashboard',
                'kind' => 'security',
                'meta' => [
                    'ip' => $ip,
                    'user_agent' => $userAgent,
                    'occurred_at' => $occurredAt->toIso8601String(),
                ],
            ],
            hours: 24,
        );
    }

    public function sendNewIpLoginAlert(
        User $user,
        string $ip,
        string $userAgent,
        CarbonInterface $occurredAt,
    ): void {
        if (! $this->isNewLoginIp($user, $ip)) {
            return;
        }

        $this->sendUniqueWithinWindow(
            user: $user,
            fingerprint: 'login-new-ip:'.$ip,
            payload: [
                'title_key' => 'systemNotifications.newIpLogin.title',
                'body_key' => 'systemNotifications.newIpLogin.body',
                'body_params' => [
                    'ip' => $ip,
                    'date' => $occurredAt->format('d.m.Y H:i'),
                ],
                'url' => '/settings/security',
                'kind' => 'security',
                'meta' => [
                    'ip' => $ip,
                    'user_agent' => $userAgent,
                    'occurred_at' => $occurredAt->toIso8601String(),
                ],
            ],
            hours: 24 * 365,
        );
    }

    public function sendSubscriptionActivated(
        User $user,
        UserSubscription $subscription,
        bool $renewed = false,
    ): void {
        $plan = strtoupper((string) $subscription->plan);
        $endsAt = $subscription->ends_at;

        if ($endsAt === null) {
            return;
        }

        $this->sendUniqueWithinWindow(
            user: $user,
            fingerprint: sprintf(
                'subscription-%s:%s:%s',
                $renewed ? 'renewed' : 'activated',
                $subscription->getKey(),
                $endsAt->toDateString(),
            ),
            payload: [
                'title_key' => $renewed
                    ? 'systemNotifications.subscriptionRenewed.title'
                    : 'systemNotifications.subscriptionActivated.title',
                'body_key' => $renewed
                    ? 'systemNotifications.subscriptionRenewed.body'
                    : 'systemNotifications.subscriptionActivated.body',
                'body_params' => [
                    'plan' => $plan,
                    'date' => $endsAt->format('d.m.Y H:i'),
                ],
                'url' => '/settings/billing',
                'kind' => 'billing',
                'meta' => [
                    'subscription_id' => $subscription->getKey(),
                    'plan' => $subscription->plan,
                    'ends_at' => $endsAt->toIso8601String(),
                    'renewed' => $renewed,
                ],
            ],
            hours: 24 * 30,
        );
    }

    public function sendPasswordChanged(
        User $user,
        string $source,
        ?string $ip = null,
    ): void {
        $this->sendUniqueWithinWindow(
            user: $user,
            fingerprint: 'password-changed:'.$source.':'.now()->format('Y-m-d-H'),
            payload: [
                'title_key' => $source === 'password_reset'
                    ? 'systemNotifications.passwordReset.title'
                    : 'systemNotifications.passwordChanged.title',
                'body_key' => $source === 'password_reset'
                    ? 'systemNotifications.passwordReset.body'
                    : 'systemNotifications.passwordChanged.body',
                'body_params' => [
                    'ipSuffix' => $ip !== null ? " (IP: {$ip})" : '',
                ],
                'url' => '/settings/security',
                'kind' => 'security',
                'meta' => [
                    'source' => $source,
                    'ip' => $ip,
                    'changed_at' => now()->toIso8601String(),
                ],
            ],
            hours: 1,
        );
    }

    public function sendSubscriptionExpiringTomorrow(
        User $user,
        UserSubscription $subscription,
    ): void {
        $plan = strtoupper((string) $subscription->plan);
        $endsAt = $subscription->ends_at;

        if ($endsAt === null) {
            return;
        }

        $this->sendUniqueWithinWindow(
            user: $user,
            fingerprint: 'subscription-expiring:'.$subscription->getKey().':'.$endsAt->toDateString(),
            payload: [
                'title_key' => 'systemNotifications.subscriptionExpiringTomorrow.title',
                'body_key' => 'systemNotifications.subscriptionExpiringTomorrow.body',
                'body_params' => [
                    'plan' => $plan,
                    'date' => $endsAt->format('d.m.Y H:i'),
                ],
                'url' => '/settings/billing',
                'kind' => 'billing',
                'meta' => [
                    'subscription_id' => $subscription->getKey(),
                    'plan' => $subscription->plan,
                    'ends_at' => $endsAt->toIso8601String(),
                ],
            ],
            hours: 48,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sendUniqueWithinWindow(
        User $user,
        string $fingerprint,
        array $payload,
        int $hours,
    ): void {
        if ($this->hasRecentFingerprint($user, $fingerprint, $hours)) {
            return;
        }

        $user->notify(new SystemDatabaseNotification([
            ...$payload,
            'fingerprint' => $fingerprint,
        ]));
    }

    private function hasRecentFingerprint(User $user, string $fingerprint, int $hours): bool
    {
        return $this->recentNotifications($user, $hours)
            ->contains(fn (DatabaseNotification $notification): bool => $this->notificationFingerprint($notification) === $fingerprint);
    }

    private function isNewLoginIp(User $user, string $ip): bool
    {
        return ! $this->recentNotifications($user, 24 * 365)
            ->contains(function (DatabaseNotification $notification) use ($ip): bool {
                $data = $notification->data;

                if (! is_array($data)) {
                    return false;
                }

                if (($data['kind'] ?? null) !== 'security') {
                    return false;
                }

                $meta = $data['meta'] ?? null;

                return is_array($meta) && ($meta['ip'] ?? null) === $ip;
            });
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    private function recentNotifications(User $user, int $hours): Collection
    {
        return $user->notifications()
            ->where('created_at', '>=', now()->subHours($hours))
            ->latest()
            ->get();
    }

    private function notificationFingerprint(DatabaseNotification $notification): ?string
    {
        $data = $notification->data;

        return is_array($data) ? ($data['fingerprint'] ?? null) : null;
    }
}
