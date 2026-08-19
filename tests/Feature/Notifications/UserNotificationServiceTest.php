<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Support\Notifications\UserNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_login_creates_greeting_and_new_ip_notifications(): void
    {
        $user = User::factory()->create();

        $this->service()->sendLoginNotifications($user, '203.0.113.10', 'Test Agent', now());

        $this->assertSame(1, $this->countNotificationsByBodyKey($user, 'systemNotifications.loginGreeting.body'));
        $this->assertSame(1, $this->countNotificationsByBodyKey($user, 'systemNotifications.newIpLogin.body'));
    }

    public function test_repeated_login_from_known_ip_does_not_create_another_new_ip_alert(): void
    {
        $user = User::factory()->create();
        $service = $this->service();

        $service->sendLoginNotifications($user, '203.0.113.10', 'Test Agent', now());
        $this->travel(1)->day();
        $service->sendLoginNotifications($user, '203.0.113.10', 'Test Agent', now());

        $this->assertSame(1, $this->countNotificationsByBodyKey($user, 'systemNotifications.newIpLogin.body'));
    }

    public function test_login_from_different_ip_creates_another_new_ip_alert(): void
    {
        $user = User::factory()->create();
        $service = $this->service();

        $service->sendLoginNotifications($user, '203.0.113.10', 'Test Agent', now());
        $service->sendLoginNotifications($user, '203.0.113.11', 'Test Agent', now()->addMinute());

        $this->assertSame(2, $this->countNotificationsByBodyKey($user, 'systemNotifications.newIpLogin.body'));
    }

    private function service(): UserNotificationService
    {
        return app(UserNotificationService::class);
    }

    private function countNotificationsByBodyKey(User $user, string $bodyKey): int
    {
        return $user->notifications()
            ->get()
            ->filter(static fn ($notification): bool => ($notification->data['body_key'] ?? null) === $bodyKey)
            ->count();
    }
}
