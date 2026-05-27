<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Dashboard\Contracts\UserDashboardServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardControllerIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_controller_passes_user_and_filters_to_service(): void
    {
        $user = User::factory()->create();
        $payload = [
            'widgets' => [
                ['key' => 'activity', 'value' => 12],
            ],
            'filters' => [
                'module_key' => 'youtube',
                'query' => 'osint',
                'period' => '7d',
                'date_from' => null,
                'date_to' => null,
            ],
        ];

        $this->mock(UserDashboardServiceInterface::class, function ($mock) use ($user, $payload): void {
            $mock->shouldReceive('build')
                ->once()
                ->with($user, [
                    'module_key' => 'youtube',
                    'query' => 'osint',
                    'period' => '7d',
                    'date_from' => null,
                    'date_to' => null,
                ])
                ->andReturn($payload);
        });

        $this
            ->actingAs($user)
            ->get(route('dashboard', [
                'module_key' => 'youtube',
                'query' => 'osint',
                'period' => '7d',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('dashboard.widgets.0.key', 'activity')
                ->where('dashboard.widgets.0.value', 12)
            );
    }
}
