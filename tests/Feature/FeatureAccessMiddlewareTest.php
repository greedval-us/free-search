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
}
