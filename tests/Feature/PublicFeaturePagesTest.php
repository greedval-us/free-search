<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PublicSite\PublicFeatureCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;
use Tests\TestCase;

class PublicFeaturePagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['inertia.ssr.enabled' => false]);
    }

    public function test_guests_can_read_every_catalog_page_without_access_to_tools(): void
    {
        $features = app(PublicFeatureCatalog::class)->pages();

        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')->where('auth.user', null)->has('features', count($features)));
        $this->get('/features')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('public/Features')->has('features', count($features)));

        foreach ($features as $feature) {
            $this->get($feature['url'])->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('public/Feature')
                ->where('feature', $feature)
                ->where('auth.user', null)
                ->missing('messages')->missing('runs'));

            $this->get($feature['workspaceUrl'])->assertRedirect(route('login'));
        }
    }

    public function test_unknown_features_return_not_found(): void
    {
        $this->get('/features/nonexistent')->assertNotFound();
        $this->get('/features/telegram/private')->assertNotFound();
    }

    public function test_public_copy_receives_current_policy_not_fixed_marketing_limits(): void
    {
        config([
            'telegram_tracking.interval_hours' => 8,
            'telegram_tracking.max_sources' => 2,
            'telegram_tracking.retention_days' => 9,
            'osint.parser_runs.retention_days' => 12,
        ]);

        $this->get('/features/telegram-tracking')->assertInertia(fn (Assert $page) => $page
            ->where('policy.hours', 8)->where('policy.sources', 2)
            ->where('policy.days', 9)->where('policy.parserDays', 12));
    }

    public function test_registration_flag_and_authenticated_user_are_preserved(): void
    {
        config(['fortify.features' => array_values(array_diff(config('fortify.features'), [Features::registration()]))]);
        $this->get('/features')->assertInertia(fn (Assert $page) => $page->where('canRegister', false));

        $user = User::factory()->create();
        $this->actingAs($user)->get('/features/telegram-tracking')->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.id', $user->id)
            ->where('feature.workspaceUrl', '/telegram?tab=tracking'));
    }

    public function test_sitemap_contains_public_descriptions_not_workspace_urls(): void
    {
        $response = $this->get('/sitemap.xml')->assertOk();
        $response->assertSee(route('features.index'), false);

        foreach (app(PublicFeatureCatalog::class)->pages() as $feature) {
            $response->assertSee(url($feature['url']), false);
            $response->assertDontSee('<loc>'.url($feature['workspaceUrl']).'</loc>', false);
        }
    }

    public function test_each_published_feature_has_complete_content_in_both_languages(): void
    {
        $fields = ['title', 'summary', 'example', 'audience', 'input', 'result', 'step1', 'step2', 'step3', 'limit'];
        foreach (['ru', 'en'] as $locale) {
            $content = json_decode(file_get_contents(resource_path("js/locales/{$locale}/publicSite.json")), true, flags: JSON_THROW_ON_ERROR);
            $this->assertEqualsCanonicalizing(array_keys(config('public_features')), array_keys($content['features']));

            foreach ($content['features'] as $feature) {
                foreach ($fields as $field) {
                    $this->assertIsString($feature[$field]);
                    $this->assertNotSame('', trim($feature[$field]));
                }
            }
        }
    }
}
