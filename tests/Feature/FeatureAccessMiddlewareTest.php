<?php

namespace Tests\Feature;

use App\Models\FeatureUsageDaily;
use App\Models\RequestLog;
use App\Models\User;
use App\Models\UserSubscription;
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
            'feature' => 'site-intel.analytics',
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
            'feature' => 'youtube.analytics',
            'reason' => 'plan',
        ]));
        $this->assertDatabaseMissing('feature_usage_daily', [
            'user_id' => $user->id,
            'feature' => 'analytics',
        ]);
    }

    public function test_site_intel_seo_audit_tab_redirects_to_billing(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/site-intel?tab=seoAudit');

        $response->assertRedirect(route('billing.edit', [
            'feature' => 'site-intel.seo-audit',
            'reason' => 'plan',
        ]));
    }

    public function test_direct_page_tab_request_redirects_when_quota_is_exhausted(): void
    {
        $user = User::factory()->create();
        UserSubscription::query()->create([
            'user_id' => $user->id,
            'plan' => User::SUBSCRIPTION_PLAN_PLUS,
            'status' => UserSubscription::STATUS_ACTIVE,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
        ]);

        FeatureUsageDaily::query()->create([
            'user_id' => $user->id,
            'feature' => 'analytics',
            'usage_date' => now()->startOfDay(),
            'used' => 10,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/site-intel?tab=seoAudit');

        $response->assertRedirect(route('billing.edit', [
            'feature' => 'site-intel.seo-audit',
            'reason' => 'quota',
        ]));
    }
}
