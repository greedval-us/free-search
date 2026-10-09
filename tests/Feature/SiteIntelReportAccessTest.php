<?php

namespace Tests\Feature;

use App\Exceptions\FeatureAccessDeniedException;
use App\Http\Middleware\EnsureSiteIntelReportsAccess;
use App\Models\FeatureUsageDaily;
use App\Models\User;
use App\Services\Access\SiteIntelReportAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SiteIntelReportAccessTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{int, int, list<string>}> */
    public static function permissionCases(): array
    {
        return [
            'analytics only' => [1, 0, ['analytics']],
            'SEO only' => [0, 1, ['seo-audit']],
            'both' => [1, 1, ['analytics', 'seo-audit']],
            'neither' => [0, 0, []],
        ];
    }

    #[DataProvider('permissionCases')]
    public function test_available_types_follow_each_resource_permission(int $analytics, int $seo, array $expected): void
    {
        $this->permissions($analytics, $seo);
        $user = User::factory()->create();

        $this->assertSame($expected, $this->access()->availableTypes($user));
        $this->assertSame(in_array('analytics', $expected, true), $this->access()->allows($user, 'analytics'));
        $this->assertSame(in_array('seo-audit', $expected, true), $this->access()->allows($user, 'seo-audit'));

        if ($expected === []) {
            $this->expectException(FeatureAccessDeniedException::class);
        }

        $this->access()->assertAny($user);
    }

    public function test_report_access_remains_available_after_daily_quota_is_exhausted_without_charging_again(): void
    {
        $this->permissions(1, 1);
        $user = User::factory()->create();
        foreach (['site-intel.analytics', 'site-intel.seo-audit'] as $resource) {
            FeatureUsageDaily::query()->create([
                'user_id' => $user->id,
                'feature' => $resource,
                'usage_date' => now()->startOfDay(),
                'used' => 1,
            ]);
        }

        $this->assertSame(['analytics', 'seo-audit'], $this->access()->availableTypes($user));
        $this->access()->ensure($user, 'analytics');
        $this->access()->ensure($user, 'seo-audit');
        $this->assertSame(2, (int) FeatureUsageDaily::query()->where('user_id', $user->id)->sum('used'));
    }

    public function test_authorizing_one_report_type_does_not_allow_the_other_type(): void
    {
        $this->permissions(1, 0);
        $user = User::factory()->create();

        try {
            $this->access()->ensure($user, 'seo-audit');
            $this->fail('The SEO report must require its own feature permission.');
        } catch (FeatureAccessDeniedException $exception) {
            $this->assertSame('site-intel.seo-audit', $exception->decision->feature);
            $this->assertFalse($exception->decision->counts);
        }
    }

    public function test_unsupported_report_types_are_denied_even_when_both_known_types_are_allowed(): void
    {
        $this->permissions(1, 1);
        $user = User::factory()->create();

        $this->assertFalse($this->access()->allows($user, 'unsupported'));
        $this->expectException(FeatureAccessDeniedException::class);
        $this->access()->ensure($user, 'unsupported');
    }

    public function test_reports_middleware_allows_seo_only_user_without_consuming_quota(): void
    {
        $this->permissions(0, 1);
        $this->registerApiRoute();
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/_site-intel-report-access')->assertOk();
        $this->assertDatabaseMissing('feature_usage_daily', ['user_id' => $user->id]);
    }

    public function test_reports_middleware_denies_user_without_either_resource(): void
    {
        $this->permissions(0, 0);
        $this->registerApiRoute();
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/_site-intel-report-access')
            ->assertForbidden()
            ->assertJsonPath('meta.feature', 'site-intel.analytics')
            ->assertJsonPath('meta.counts', false);
    }

    public function test_page_middleware_only_restricts_reports_tab(): void
    {
        $this->permissions(0, 0);
        Route::get('/_site-intel-report-page', static fn () => response()->json(['ok' => true]))
            ->middleware(['auth', EnsureSiteIntelReportsAccess::class])
            ->name('site-intel');
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/_site-intel-report-page?tab=siteHealth')->assertOk();
        $this->getJson('/_site-intel-report-page?tab=reports')->assertForbidden();
    }

    private function registerApiRoute(): void
    {
        Route::get('/_site-intel-report-access', static fn () => response()->json(['ok' => true]))
            ->middleware(['auth', EnsureSiteIntelReportsAccess::class])
            ->name('site-intel.reports.access-test');
    }

    private function permissions(int $analytics, int $seo): void
    {
        Config::set('access.plans.free', [
            ...config('access.plans.free'),
            'site-intel.analytics' => $analytics,
            'site-intel.seo-audit' => $seo,
        ]);
    }

    private function access(): SiteIntelReportAccess
    {
        return $this->app->make(SiteIntelReportAccess::class);
    }
}
