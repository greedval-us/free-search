<?php

namespace Tests\Feature;

use App\Exceptions\Public\ExternalServiceUnavailableException;
use App\Models\User;
use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel\NewsMentionDeduplicator;
use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel\NewsMentionFingerprintFactory;
use App\Modules\NewsMediaIntel\Application\Support\NewsMediaIntelConfig;
use App\Modules\NewsMediaIntel\Infrastructure\Feeds\SearxngNewsFeedFetcher;
use App\Support\Observability\ExternalServiceLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SearxngNewsFeedFetcherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    public function test_lookup_uses_only_searxng_and_preserves_analysis_contract(): void
    {
        config(['osint.news_media_intel.searxng.max_pages' => 1]);
        Http::fake(['http://127.0.0.1:8088/search' => Http::response(['results' => [
            $this->article('a', ['title' => 'Research &amp; growth', 'content' => '<b>Important</b> research', 'publishedDate' => '2026-10-05T12:00:00Z']),
            $this->article('b'),
        ]])]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('news-media-intel.lookup', ['query' => ' research ', 'locale' => 'en']))
            ->assertOk()
            ->assertJsonCount(2, 'data.mentions')
            ->assertJsonPath('data.mentions.0.source', 'searxng')
            ->assertJsonPath('data.mentions.0.title', 'Research & growth')
            ->assertJsonPath('data.mentions.0.snippet', 'Important research')
            ->assertJsonPath('data.mentions.1.publishedAt', '')
            ->assertJsonCount(1, 'data.timeline')
            ->assertJsonPath('data.timeline.0.date', '2026-10-05')
            ->assertJsonStructure(['data' => ['query', 'mentions', 'topics', 'timeline', 'sentiment']]);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'http://127.0.0.1:8088/search'
            && $request->hasHeader('Accept', 'application/json')
            && str_contains($request->header('Content-Type')[0], 'application/x-www-form-urlencoded')
            && $request['q'] === 'research' && $request['categories'] === 'news'
            && $request['format'] === 'json' && $request['language'] === 'ru' && $request['pageno'] === 1);
        Http::assertSentCount(1);
    }

    public function test_engine_and_time_range_preferences_are_sent_in_the_body(): void
    {
        Http::fake(['http://127.0.0.1:8088/search' => Http::response(['results' => []])]);
        $this->fetcher(['engines' => ['google news', 'bing news'], 'time_range' => 'month', 'safe_search' => 2])->fetchAll('pilot');

        Http::assertSent(fn (Request $request): bool => $request['engines'] === 'google news,bing news'
            && $request['time_range'] === 'month' && $request['safesearch'] === 2);
    }

    #[DataProvider('invalidPublicationDates')]
    public function test_incomplete_or_invalid_publication_dates_cannot_fabricate_timeline_entries(string $date): void
    {
        config(['osint.news_media_intel.searxng.max_pages' => 1]);
        Http::fake(['http://127.0.0.1:8088/search' => Http::response(['results' => [
            $this->article('unknown-date', ['publishedDate' => $date]),
        ]])]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('news-media-intel.lookup', ['query' => 'research', 'locale' => 'en']))
            ->assertOk()
            ->assertJsonCount(1, 'data.mentions')
            ->assertJsonPath('data.mentions.0.publishedAt', '')
            ->assertJsonCount(0, 'data.timeline');
    }

    public static function invalidPublicationDates(): array
    {
        return [['2026'], ['October 2026'], ['2026-02-31']];
    }

    public function test_pagination_retains_a_richer_duplicate_and_stops_at_unique_limit(): void
    {
        Http::fake(['http://127.0.0.1:8088/search' => Http::sequence()
            ->push(['results' => [$this->article('a')]])
            ->push(['results' => [$this->article('a', ['content' => 'Richer article summary', 'publishedDate' => 'Mon, 05 Oct 2026 12:00:00 GMT']), $this->article('b')]])]);

        $items = $this->fetcher(['max_pages' => 5], 2)->fetchAll('pilot');

        $this->assertCount(2, $items);
        $this->assertSame('Richer article summary', $items[0]->snippet);
        $this->assertSame('2026-10-05T12:00:00+00:00', $items[0]->publishedAt);
        Http::assertSent(fn (Request $request): bool => $request['pageno'] === 2);
        Http::assertSentCount(2);
    }

    public function test_an_engine_ignoring_pageno_does_not_cause_repeated_requests(): void
    {
        Http::fake(['http://127.0.0.1:8088/search' => Http::response(['results' => [$this->article('a')]])]);

        $this->assertCount(1, $this->fetcher(['max_pages' => 10])->fetchAll('pilot'));
        Http::assertSentCount(2);
    }

    public function test_maximum_page_count_bounds_even_a_non_terminating_engine(): void
    {
        Http::fake(fn (Request $request) => Http::response(['results' => [$this->article('page-'.$request['pageno'])]]));

        $this->assertCount(10, $this->fetcher(['max_pages' => 999])->fetchAll('pilot'));
        Http::assertSentCount(10);
    }

    public function test_a_later_page_failure_keeps_already_collected_articles(): void
    {
        Http::fake(['http://127.0.0.1:8088/search' => Http::sequence()
            ->push(['results' => [$this->article('a')]])->push([], 503)]);

        $this->assertCount(1, $this->fetcher()->fetchAll('pilot'));
        Http::assertSentCount(2);
    }

    public function test_unsafe_urls_and_invalid_records_cannot_become_article_links(): void
    {
        Http::fake(['http://127.0.0.1:8088/search' => Http::response(['results' => [
            $this->article('safe'),
            $this->article('bad', ['url' => 'javascript:alert(1)']),
            $this->article('bad', ['url' => 'https://name:password@example.test/']),
            $this->article('bad', ['url' => "https://example.test/\narticle"]),
            $this->article('bad', ['url' => 'https://example.test\\@evil.test/article']),
            $this->article('bad', ['url' => '/relative']),
            $this->article('bad', ['title' => ['unexpected' => 'object']]),
            'invalid record',
        ]])]);

        $items = $this->fetcher(['max_pages' => 1])->fetchAll('pilot');

        $this->assertCount(1, $items);
        $this->assertSame('https://example.test/safe', $items[0]->link);
    }

    #[DataProvider('invalidEndpoints')]
    public function test_an_invalid_endpoint_is_rejected_before_any_request(string $url): void
    {
        Http::fake();
        try {
            $this->fetcher(['base_url' => $url])->fetchAll('pilot');
            $this->fail('Expected a configuration error.');
        } catch (ExternalServiceUnavailableException $exception) {
            $this->assertSame('news_search_configuration', $exception->errorCode());
        }
        Http::assertNothingSent();
    }

    public static function invalidEndpoints(): array
    {
        return [['file:///tmp/search'], ['https://name:password@example.test'], ['https://example.test?token=secret'], ['https://example.test/#fragment'], ['']];
    }

    #[DataProvider('unavailableResponses')]
    public function test_initial_failures_are_not_reported_as_successful_empty_searches(mixed $body, int $status): void
    {
        config(['osint.news_media_intel.searxng.max_pages' => 1]);
        Http::fake(['http://127.0.0.1:8088/search' => Http::response($body, $status)]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('news-media-intel.lookup', ['query' => 'pilot', 'locale' => 'ru']))
            ->assertStatus(503)
            ->assertJsonPath('code', 'news_search_unavailable')
            ->assertJsonPath('message', 'Поиск новостей временно недоступен. Попробуйте позже.');
        Http::assertSentCount(1);
    }

    public static function unavailableResponses(): array
    {
        return [
            'json disabled' => [[], 403],
            'rate limit' => [[], 429],
            'upstream failed' => [[], 503],
            'redirect' => [[], 302],
            'html instead of json' => ['<html>Blocked</html>', 200],
            'unexpected shape' => [['data' => []], 200],
            'search error' => [['error' => 'internal error', 'results' => []], 200],
            'engines unavailable' => [['results' => [], 'unresponsive_engines' => [['bing news', 'timeout']]], 200],
        ];
    }

    public function test_connection_failure_is_a_public_service_error(): void
    {
        Http::fake(['http://127.0.0.1:8088/search' => Http::failedConnection()]);
        $this->expectException(ExternalServiceUnavailableException::class);

        $this->fetcher()->fetchAll('pilot');
    }

    public function test_an_empty_query_never_contacts_searxng(): void
    {
        Http::fake();
        $this->assertSame([], $this->fetcher()->fetchAll('  '));
        Http::assertNothingSent();
    }

    private function fetcher(array $preferences = [], int $limit = 120): SearxngNewsFeedFetcher
    {
        $config = NewsMediaIntelConfig::fromArray(['searxng' => $preferences, 'service' => ['max_mentions' => $limit]]);

        return new SearxngNewsFeedFetcher($config, app(ExternalServiceLogger::class), new NewsMentionDeduplicator(new NewsMentionFingerprintFactory($config)));
    }

    private function article(string $id, array $overrides = []): array
    {
        return [...['url' => 'https://example.test/'.$id, 'title' => 'Research '.$id, 'content' => 'Summary '.$id], ...$overrides];
    }
}
