<?php

namespace Tests\Feature;

use App\Models\FeatureUsageDaily;
use App\Models\User;
use App\Modules\Telegram\Analytics\TelegramAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TelegramAnalyticsSnapshotsTest extends TestCase
{
    use RefreshDatabase;

    public function test_comparison_is_built_server_side_with_one_credit_and_report_is_read_only(): void
    {
        config()->set('access.plans.free.telegram.analytics', 1);
        $this->mock(TelegramAnalyticsService::class, function ($mock): void {
            $mock->shouldReceive('build')->once()->with(
                'example', Mockery::on(fn ($date) => $date->toDateString() === '2026-09-02'),
                Mockery::on(fn ($date) => $date->toDateString() === '2026-09-03'), 'balanced', null,
            )->andReturn(['marker' => 'current']);
            $mock->shouldReceive('build')->once()->with(
                'example', Mockery::on(fn ($date) => $date->toDateString() === '2026-08-31'),
                Mockery::on(fn ($date) => $date->toDateString() === '2026-09-01'), 'balanced', null,
            )->andReturn(['marker' => 'previous']);
        });
        $user = User::factory()->create();
        $parameters = ['chatUsername' => 'example', 'dateFrom' => '2026-09-02', 'dateTo' => '2026-09-03'];
        $this->actingAs($user)->getJson(route('telegram.analytics.summary', $parameters))
            ->assertOk()->assertJsonPath('data.previousReport.marker', 'previous');
        $this->get(route('telegram.analytics.report', $parameters))->assertOk();
        $this->assertSame(1, FeatureUsageDaily::query()->where('user_id', $user->id)->sum('used'));
        $this->getJson(route('telegram.analytics.summary', [...$parameters, 'snapshotRole' => 'previous']))->assertTooManyRequests();
        $this->actingAs(User::factory()->create())->getJson(route('telegram.analytics.report', $parameters))->assertStatus(410);
    }
}
