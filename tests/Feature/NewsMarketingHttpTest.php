<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Reports\ReportSnapshotStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class NewsMarketingHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
        $this->travelTo(now()->setDate(2026, 10, 9)->setTime(10, 0));
        config(['osint.news_media_intel.searxng.max_pages' => 1, 'inertia.ssr.enabled' => false]);
    }

    public function test_options_expose_user_filters_without_disclosing_server_address_or_contacting_it(): void
    {
        $this->actingAs(User::factory()->create())->getJson('/news-media-intel/options')
            ->assertOk()->assertJsonPath('data.categories', ['news', 'general'])
            ->assertJsonPath('data.defaults.language', 'ru')->assertJsonPath('data.maxPages', 1)
            ->assertJsonPath('data.engines.general.0', 'google')->assertDontSee('127.0.0.1');
        Http::assertNothingSent();
    }

    public function test_advanced_lookup_sends_validated_filters_and_returns_provenance_and_search_extras(): void
    {
        Http::fake(['http://127.0.0.1:8088/search' => Http::response([
            'results' => [$this->article('https://publisher.com/story', 'Acme growth', 'Acme research', 'general', ['bing'])],
            'suggestions' => ['Acme comparison'], 'corrections' => ['ACME'], 'answers' => ['An answer'],
            'unresponsive_engines' => [['google', 'timeout']],
        ])]);
        $this->actingAs(User::factory()->create())->getJson('/news-media-intel/lookup?'.http_build_query([
            'query' => 'site:publisher.com Acme -advertisement', 'categories' => ['general'], 'language' => 'en',
            'timeRange' => 'week', 'safeSearch' => 2, 'engines' => ['bing'], 'maxPages' => 1,
        ]))->assertOk()->assertJsonPath('data.mentions.0.publisher', 'publisher.com')
            ->assertJsonPath('data.mentions.0.engines', ['bing'])->assertJsonPath('data.mentions.0.position', 1)
            ->assertJsonPath('data.suggestions', ['Acme comparison'])
            ->assertJsonPath('data.coverage.unresponsiveEngines.0.name', 'google');
        Http::assertSent(fn (Request $request): bool => $request['q'] === 'site:publisher.com Acme -advertisement'
            && $request['language'] === 'en' && $request['time_range'] === 'week' && $request['safesearch'] === 2
            && $request['engines'] === 'bing' && ! isset($request['categories']));
    }

    public function test_explicit_all_time_can_clear_the_server_default_period(): void
    {
        config(['osint.news_media_intel.searxng.time_range' => 'month']);
        Http::fake(['http://127.0.0.1:8088/search' => Http::response(['results' => []])]);
        $this->actingAs(User::factory()->create())->getJson('/news-media-intel/lookup?query=Acme&timeRange=')->assertOk();
        Http::assertSent(fn (Request $request): bool => ! isset($request['time_range']));
    }

    public function test_analytics_combines_news_and_web_and_downloads_an_owned_snapshot_without_a_second_search(): void
    {
        Http::fake(fn (Request $request) => Http::response(['results' => ($request['categories'] ?? '') === 'general'
            ? [$this->article('https://acme.com/guide', 'Acme and Beta guide', '<script>alert(1)</script> Research growth', 'general', ['google'])]
            : [$this->article('https://publisher.com/news', 'Acme and Beta launch', 'A useful product comparison', 'news', ['google news'])],
            'suggestions' => ['How to choose Acme?']]));
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/news-media-intel/analytics', [
            'query' => ' Acme products ', 'brand' => 'Acme', 'competitors' => ['Beta'], 'domain' => 'HTTPS://ACME.COM/catalog',
            'language' => 'en', 'timeRange' => 'month', 'maxPages' => 1,
        ])->assertOk()->assertJsonPath('data.query', 'Acme products')->assertJsonPath('data.summary.mentions', 2)
            ->assertJsonPath('data.visibility.domain', 'acme.com')->assertJsonPath('data.visibility.domainMatches', 1)
            ->assertJsonPath('data.brandComparison.totalMatches', 4)->assertJsonPath('data.brandComparison.entities.0.share', 50)
            ->assertJsonStructure(['data' => ['reportId', 'reportExpiresAt', 'coverage', 'contentOpportunities']]);
        Http::assertSentCount(2);
        $id = $response->json('data.reportId');
        $this->assertTrue(Str::isUuid($id));
        $this->get('/news-media-intel/report/'.$id.'?locale=ru')->assertOk()
            ->assertSee('Новости и медиа: аналитика для SEO и маркетинга')->assertSee('Acme and Beta guide')
            ->assertDontSee('<script>alert(1)</script>', false)->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get('/news-media-intel/report/'.$id.'?locale=en&download=1')->assertOk()
            ->assertSee('News and media: SEO and marketing analytics')->assertHeader('Content-Disposition');
        $this->getJson('/news-media-intel/report/'.$id.'?format=json')->assertOk()
            ->assertJsonPath('query', 'Acme products')->assertJsonPath('reportId', $id)->assertHeader('Content-Disposition');
        Http::assertSentCount(2);
        $this->assertDatabaseCount('feature_usage_daily', 0);
        $this->actingAs(User::factory()->create())->getJson('/news-media-intel/report/'.$id.'?format=json')->assertGone();
        $this->actingAs($user);
        $this->travel(3601)->seconds();
        $this->getJson('/news-media-intel/report/'.$id.'?format=json')->assertGone();
        Http::assertSentCount(2);
    }

    public static function invalidInput(): array
    {
        return [
            [['query' => '!google Acme'], 'query'], [['query' => 'Acme :en'], 'query'], [['query' => '!! Acme'], 'query'],
            [['query' => "Acme\nBeta"], 'query'], [['query' => ' '], 'query'],
            [['categories' => ['images']], 'categories.0'], [['categories' => ['news', 'news']], 'categories.0'],
            [['language' => 'unsupported'], 'language'], [['timeRange' => 'century'], 'timeRange'], [['safeSearch' => 3], 'safeSearch'],
            [['engines' => ['http://evil.com']], 'engines.0'], [['maxPages' => 10], 'maxPages'],
            [['domain' => 'javascript:alert(1)'], 'domain'], [['domain' => 'https://user:secret@example.com'], 'domain'],
            [['domain' => '127.0.0.1'], 'domain'], [['domain' => 'https://example.com:8080/'], 'domain'],
            [['domain' => '.example.com'], 'domain'], [['domain' => 'https://..example.com/'], 'domain'],
            [['brand' => 'Acme', 'competitors' => ['acme']], 'competitors'], [['competitors' => ['Beta', 'beta']], 'competitors.0'],
            [['competitors' => ['A', 'B', 'C', 'D']], 'competitors'],
        ];
    }

    #[DataProvider('invalidInput')]
    public function test_rejects_invalid_or_ambiguous_analysis_inputs_before_any_network_request(array $input, string $field): void
    {
        $this->actingAs(User::factory()->create())->postJson('/news-media-intel/analytics', [...['query' => 'Acme products'], ...$input])
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        Http::assertNothingSent();
    }

    public function test_guest_and_unverified_accounts_cannot_read_or_generate_reports(): void
    {
        $this->getJson('/news-media-intel/options')->assertUnauthorized();
        $this->postJson('/news-media-intel/analytics', ['query' => 'Acme'])->assertUnauthorized();
        $this->getJson('/news-media-intel/report/'.Str::uuid().'?format=json')->assertUnauthorized();
        $this->actingAs(User::factory()->unverified()->create())->postJson('/news-media-intel/analytics', ['query' => 'Acme'])->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_report_html_escapes_text_and_never_links_unsafe_stored_urls(): void
    {
        $user = User::factory()->create();
        $id = (string) Str::uuid();
        $report = ['query' => '<script>query</script>', 'checkedAt' => now()->toIso8601String(),
            'options' => ['language' => 'en', 'timeRange' => ''], 'summary' => [], 'visibility' => ['domain' => ''],
            'mentions' => [['title' => '<script>source</script>', 'snippet' => '<img src=x onerror=alert(1)>', 'link' => 'javascript:alert(1)', 'publishedAt' => '']]];
        app(ReportSnapshotStore::class)->store($user->id, 'news-media-intel.analytics', ['id' => $id], $report);
        $this->actingAs($user)->get('/news-media-intel/report/'.$id.'?locale=en')->assertOk()
            ->assertSee('&lt;script&gt;query&lt;/script&gt;', false)->assertDontSee('<script>', false)
            ->assertDontSee('href="javascript:', false)->assertDontSee('<img ', false);
        Http::assertNothingSent();
    }

    private function article(string $url, string $title, string $snippet, string $category, array $engines): array
    {
        return ['url' => $url, 'title' => $title, 'content' => $snippet, 'category' => $category,
            'engines' => $engines, 'publishedDate' => '2026-10-08T12:00:00Z'];
    }
}
