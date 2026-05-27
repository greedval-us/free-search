<?php

namespace Tests\Feature;

use App\Models\FeatureUsageDaily;
use App\Models\RequestLog;
use App\Models\User;
use App\Modules\YouTube\DTO\Request\YouTubeCommentsQueryDTO;
use App\Modules\YouTube\Parser\Contracts\YouTubeParserApplicationServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\Feature\Concerns\CreatesSubscribedUser;
use Tests\TestCase;

class FeatureAccessMiddlewareTest extends TestCase
{
    use CreatesSubscribedUser;
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
            'feature' => 'youtube.analytics',
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

    public function test_page_tab_access_can_be_added_from_config(): void
    {
        Route::get('/_custom-access-page', static fn () => response('ok'))
            ->middleware('feature.access')
            ->name('custom-access-page');

        Config::set('access.page_resources', [
            ...config('access.page_resources'),
            'custom-access-page' => [
                'tabs' => [
                    'deepAnalytics' => 'telegram.analytics',
                ],
            ],
        ]);

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/_custom-access-page?tab=deepAnalytics');

        $response->assertRedirect(route('billing.edit', [
            'feature' => 'telegram.analytics',
            'reason' => 'plan',
        ]));
    }

    public function test_direct_page_tab_request_redirects_when_quota_is_exhausted(): void
    {
        $user = $this->createSubscribedUser();

        FeatureUsageDaily::query()->create([
            'user_id' => $user->id,
            'feature' => 'site-intel.seo-audit',
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

    public function test_previous_analytics_snapshot_does_not_consume_quota(): void
    {
        Route::get('/_feature-access-summary-test', static fn () => response()->json(['ok' => true]))
            ->middleware('feature.access')
            ->name('feature-access.summary-test');

        Config::set('access.protected_routes', [
            ...config('access.protected_routes'),
            'feature-access.summary-test' => [
                'resource' => 'telegram.analytics',
                'counts' => true,
            ],
        ]);

        $user = $this->createSubscribedUser();

        $this
            ->actingAs($user)
            ->getJson('/_feature-access-summary-test?snapshotRole=current')
            ->assertOk();

        $this
            ->actingAs($user)
            ->getJson('/_feature-access-summary-test?snapshotRole=previous')
            ->assertOk();

        $this->assertDatabaseHas('feature_usage_daily', [
            'user_id' => $user->id,
            'feature' => 'telegram.analytics',
            'used' => 1,
        ]);
    }

    public function test_non_counting_query_values_can_be_added_from_config(): void
    {
        Route::get('/_feature-access-non-counting-query-test', static fn () => response()->json(['ok' => true]))
            ->middleware('feature.access')
            ->name('feature-access.non-counting-query-test');

        Config::set('access.protected_routes', [
            ...config('access.protected_routes'),
            'feature-access.non-counting-query-test' => [
                'resource' => 'telegram.analytics',
                'counts' => true,
            ],
        ]);
        Config::set('access.non_counting_query_values', [
            ...config('access.non_counting_query_values'),
            'mode' => [
                'preview',
            ],
        ]);

        $user = $this->createSubscribedUser();

        $this
            ->actingAs($user)
            ->getJson('/_feature-access-non-counting-query-test?mode=preview')
            ->assertOk();

        $this->assertDatabaseMissing('feature_usage_daily', [
            'user_id' => $user->id,
            'feature' => 'telegram.analytics',
        ]);
    }

    public function test_non_counting_routes_allow_paid_user_without_consuming_quota(): void
    {
        Route::get('/_feature-access-report-test', static fn () => response()->json(['ok' => true]))
            ->middleware('feature.access')
            ->name('feature-access.report-test');

        Config::set('access.protected_routes', [
            ...config('access.protected_routes'),
            'feature-access.report-test' => [
                'resource' => 'telegram.analytics',
                'counts' => false,
            ],
        ]);

        $user = $this->createSubscribedUser();
        FeatureUsageDaily::query()->create([
            'user_id' => $user->id,
            'feature' => 'telegram.analytics',
            'usage_date' => now()->startOfDay(),
            'used' => 10,
        ]);

        $this
            ->actingAs($user)
            ->getJson('/_feature-access-report-test')
            ->assertOk();

        $this->assertDatabaseHas('feature_usage_daily', [
            'user_id' => $user->id,
            'feature' => 'telegram.analytics',
            'used' => 10,
        ]);
    }

    public function test_youtube_search_comments_preview_does_not_consume_parser_quota(): void
    {
        $user = $this->createSubscribedUser();

        $this->mock(YouTubeParserApplicationServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('comments')
                ->once()
                ->with(Mockery::type(YouTubeCommentsQueryDTO::class))
                ->andReturn([
                    'items' => [],
                    'pagination' => [
                        'nextPageToken' => null,
                    ],
                ]);
        });

        $this
            ->actingAs($user)
            ->getJson(route('youtube.parser.comments', [
                'videoId' => 'video123',
                'quotaContext' => 'youtube-search-comments-preview',
            ]))
            ->assertOk();

        $this->assertDatabaseMissing('feature_usage_daily', [
            'user_id' => $user->id,
            'feature' => 'youtube.parser',
        ]);
    }

    public function test_youtube_search_comments_preview_is_allowed_even_when_parser_quota_is_exhausted(): void
    {
        $user = $this->createSubscribedUser();
        FeatureUsageDaily::query()->create([
            'user_id' => $user->id,
            'feature' => 'youtube.parser',
            'usage_date' => now()->startOfDay(),
            'used' => 5,
        ]);

        $this->mock(YouTubeParserApplicationServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('comments')
                ->once()
                ->with(Mockery::type(YouTubeCommentsQueryDTO::class))
                ->andReturn([
                    'items' => [],
                    'pagination' => [
                        'nextPageToken' => null,
                    ],
                ]);
        });

        $this
            ->actingAs($user)
            ->getJson(route('youtube.parser.comments', [
                'videoId' => 'video123',
                'quotaContext' => 'youtube-search-comments-preview',
            ]))
            ->assertOk();

        $this->assertDatabaseHas('feature_usage_daily', [
            'user_id' => $user->id,
            'feature' => 'youtube.parser',
            'used' => 5,
        ]);
    }

    public function test_quota_context_is_ignored_for_other_routes(): void
    {
        Route::get('/_feature-access-quota-context-scope-test', static fn () => response()->json(['ok' => true]))
            ->middleware('feature.access')
            ->name('feature-access.quota-context-scope-test');

        Config::set('access.protected_routes', [
            ...config('access.protected_routes'),
            'feature-access.quota-context-scope-test' => [
                'resource' => 'telegram.analytics',
                'counts' => true,
            ],
        ]);

        $user = $this->createSubscribedUser();

        $this
            ->actingAs($user)
            ->getJson('/_feature-access-quota-context-scope-test?quotaContext=youtube-search-comments-preview')
            ->assertOk();

        $this->assertDatabaseHas('feature_usage_daily', [
            'user_id' => $user->id,
            'feature' => 'telegram.analytics',
            'used' => 1,
        ]);
    }
}
