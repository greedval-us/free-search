<?php

namespace Tests\Feature;

use App\Models\RequestLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureAccessMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_denied_feature_request_is_not_written_to_dashboard_activity(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->getJson(route('site-intel.analytics', ['target' => 'example.com']));

        $response->assertForbidden();
        $this->assertSame(0, RequestLog::query()->count());
    }

    public function test_browser_feature_request_redirects_to_billing_page(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('site-intel.analytics', ['target' => 'example.com']));

        $response->assertRedirect(route('billing.edit', [
            'feature' => 'analytics',
            'reason' => 'plan',
        ]));
        $this->assertSame(0, RequestLog::query()->count());
    }

    public function test_direct_page_tab_request_redirects_to_billing_without_consuming_quota(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/youtube?tab=analytics');

        $response->assertRedirect(route('billing.edit', [
            'feature' => 'analytics',
            'reason' => 'plan',
        ]));
        $this->assertDatabaseMissing('feature_usage_daily', [
            'user_id' => $user->id,
            'feature' => 'analytics',
        ]);
    }
}
