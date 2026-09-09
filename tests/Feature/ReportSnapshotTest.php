<?php

namespace Tests\Feature;

use App\Models\FeatureUsageDaily;
use App\Models\User;
use App\Modules\SiteIntel\Application\Contracts\SeoAuditServiceInterface;
use App\Modules\SiteIntel\DTO\Result\SeoAuditResultDTO;
use App\Support\Reports\ReportSnapshotStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Tests\TestCase;

class ReportSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshot_flag_does_not_allow_two_seo_requests_with_one_daily_credit(): void
    {
        config()->set('access.plans.free.site-intel.seo-audit', 1);
        $this->mock(SeoAuditServiceInterface::class)->shouldReceive('audit')->once()->andReturn(new SeoAuditResultDTO([]));
        $user = User::factory()->create();
        $this->actingAs($user)->getJson(route('site-intel.seo-audit', ['target' => 'example.com', 'snapshotRole' => 'previous']))->assertOk();
        $this->getJson(route('site-intel.seo-audit', ['target' => 'example.org', 'snapshotRole' => 'previous']))->assertTooManyRequests();
        $this->assertSame(1, FeatureUsageDaily::query()->where('user_id', $user->id)->sum('used'));
    }

    public function test_saved_seo_report_can_be_downloaded_after_quota_is_exhausted_without_new_analysis(): void
    {
        config()->set('access.plans.free.site-intel.seo-audit', 1);
        $this->mock(SeoAuditServiceInterface::class)->shouldReceive('audit')->once()->andReturn(new SeoAuditResultDTO([]));
        $user = User::factory()->create();
        $this->actingAs($user)->getJson(route('site-intel.seo-audit', ['target' => 'example.com']))->assertOk();
        foreach (['en', 'ru'] as $locale) {
            $this->get(route('site-intel.seo-report', ['target' => 'example.com', 'locale' => $locale, 'download' => 1]))
                ->assertOk()->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        }
        $this->assertSame(1, FeatureUsageDaily::query()->where('user_id', $user->id)->sum('used'));
        $this->getJson(route('site-intel.seo-report', ['target' => 'example.org']))->assertStatus(410);
    }

    #[DataProvider('reportRoutes')]
    public function test_report_without_a_snapshot_never_starts_collection(string $route, array $parameters): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route($route, $parameters))->assertStatus(410)->assertJsonPath('code', 'report_expired');
    }

    public static function reportRoutes(): array
    {
        return [
            ['site-intel.seo-report', ['target' => 'example.com']],
            ['site-intel.report', ['target' => 'example.com']],
            ['youtube.analytics.report', ['videoId' => 'abcdefghijk']],
            ['mastodon.analytics.report', ['target' => 'example']],
            ['bluesky.analytics.report', ['target' => 'example.bsky.social']],
            ['telegram.analytics.report', ['chatUsername' => 'example']],
        ];
    }

    public function test_snapshots_are_scoped_to_user_feature_parameters_and_expiry(): void
    {
        config()->set('access.report_snapshot_ttl_seconds', 60);
        $store = app(ReportSnapshotStore::class);
        $store->store(1, 'seo', ['target' => 'example.com', 'limit' => 3], ['score' => 80]);
        $this->assertSame(['score' => 80], $store->get(1, 'seo', ['limit' => 3, 'target' => 'example.com']));
        foreach ([[2, 'seo', ['target' => 'example.com', 'limit' => 3]], [1, 'other', ['target' => 'example.com', 'limit' => 3]], [1, 'seo', ['target' => 'example.org', 'limit' => 3]]] as $arguments) {
            try {
                $store->get(...$arguments);
                $this->fail('An unrelated snapshot must not be accessible.');
            } catch (GoneHttpException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->travel(61)->seconds();
        $this->expectException(GoneHttpException::class);
        $store->get(1, 'seo', ['target' => 'example.com', 'limit' => 3]);
    }
}
