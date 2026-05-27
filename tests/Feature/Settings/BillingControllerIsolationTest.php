<?php

namespace Tests\Feature\Settings;

use App\Models\FeatureUsageDaily;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BillingControllerIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_page_uses_summary_service_payload(): void
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
            'feature' => 'telegram.analytics',
            'usage_date' => now()->startOfDay(),
            'used' => 2,
        ]);

        $this
            ->actingAs($user)
            ->get(route('billing.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/Billing')
                ->where('access.plan', 'plus')
                ->where('access.features', static function (Collection|array $features): bool {
                    $data = $features instanceof Collection ? $features->toArray() : $features;

                    return ($data['telegram.analytics']['remaining'] ?? null) === 8
                        && ($data['telegram.analytics']['used'] ?? null) === 2;
                })
                ->has('plans')
            );
    }
}
