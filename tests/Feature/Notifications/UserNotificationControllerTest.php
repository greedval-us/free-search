<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\SystemDatabaseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserNotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_read_redirects_to_safe_local_path(): void
    {
        [$user, $notificationId] = $this->userWithNotification();

        $response = $this->actingAs($user)->post(route('notifications.read', $notificationId), [
            'redirect_to' => '/dashboard',
        ]);

        $response->assertRedirect('/dashboard');
    }

    public function test_mark_read_rejects_protocol_relative_redirect(): void
    {
        [$user, $notificationId] = $this->userWithNotification();

        $response = $this->actingAs($user)
            ->from('/settings/notifications')
            ->post(route('notifications.read', $notificationId), [
                'redirect_to' => '//attacker.example/path',
            ]);

        $response->assertRedirect('/settings/notifications');
    }

    /**
     * @return array{User, string}
     */
    private function userWithNotification(): array
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $user->notify(new SystemDatabaseNotification([
            'title_key' => 'systemNotifications.welcome.title',
            'body_key' => 'systemNotifications.welcome.body',
        ]));

        return [$user, (string) $user->notifications()->value('id')];
    }
}
