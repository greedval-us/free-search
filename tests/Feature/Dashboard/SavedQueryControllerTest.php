<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use App\Models\UserSavedQuery;
use App\Services\Dashboard\Contracts\SavedQueryServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SavedQueryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_calls_saved_query_service_and_redirects_back(): void
    {
        $user = User::factory()->create();

        $this->mock(SavedQueryServiceInterface::class, function ($mock) use ($user): void {
            $mock->shouldReceive('saveFromRequestLog')
                ->once()
                ->with($user, 123);
        });

        $this
            ->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('dashboard.saved-queries.store'), [
                'request_log_id' => 123,
            ])
            ->assertRedirect(route('dashboard'));
    }

    public function test_store_validates_request_log_id(): void
    {
        $user = User::factory()->create();

        $this->mock(SavedQueryServiceInterface::class, function ($mock): void {
            $mock->shouldNotReceive('saveFromRequestLog');
        });

        $this
            ->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('dashboard.saved-queries.store'), [
                'request_log_id' => 0,
            ])
            ->assertSessionHasErrors('request_log_id');
    }

    public function test_destroy_calls_saved_query_service_with_bound_model_and_redirects_back(): void
    {
        $user = User::factory()->create();
        $savedQuery = UserSavedQuery::query()->create([
            'user_id' => $user->id,
            'module_key' => 'youtube',
            'query_preview' => 'osint',
            'method' => 'GET',
            'path' => '/youtube/search/videos',
            'run_url' => '/youtube/search/videos?q=osint',
            'payload' => ['q' => 'osint'],
            'last_used_at' => now(),
        ]);

        $this->mock(SavedQueryServiceInterface::class, function ($mock) use ($user, $savedQuery): void {
            $mock->shouldReceive('deleteForUser')
                ->once()
                ->with(
                    $user,
                    Mockery::on(static fn (UserSavedQuery $model): bool => $model->id === $savedQuery->id
                        && $model->user_id === $savedQuery->user_id)
                );
        });

        $this
            ->actingAs($user)
            ->from(route('dashboard'))
            ->delete(route('dashboard.saved-queries.destroy', ['savedQuery' => $savedQuery->id]))
            ->assertRedirect(route('dashboard'));
    }
}
