<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use App\Services\Dashboard\Contracts\ModulePinServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModulePinControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_toggle_calls_module_pin_service_and_redirects_back(): void
    {
        $user = User::factory()->create();

        $this->mock(ModulePinServiceInterface::class, function ($mock) use ($user): void {
            $mock->shouldReceive('toggle')
                ->once()
                ->with($user, 'youtube');
        });

        $this
            ->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('dashboard.module-pins.toggle'), [
                'module_key' => 'youtube',
            ])
            ->assertRedirect(route('dashboard'));
    }

    public function test_toggle_validates_module_key_against_registry(): void
    {
        $user = User::factory()->create();

        $this->mock(ModulePinServiceInterface::class, function ($mock): void {
            $mock->shouldNotReceive('toggle');
        });

        $this
            ->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('dashboard.module-pins.toggle'), [
                'module_key' => 'unknown-module',
            ])
            ->assertSessionHasErrors('module_key');
    }
}
