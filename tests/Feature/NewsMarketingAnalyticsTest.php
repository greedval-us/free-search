<?php

namespace Tests\Feature;

use App\Exceptions\Public\ExternalServiceUnavailableException;
use App\Modules\NewsMediaIntel\Application\Contracts\SearxngSearchClientInterface;
use App\Modules\NewsMediaIntel\Application\Services\Marketing\NewsMarketingAnalyticsService;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsMarketingLookupDTO;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsMentionDTO;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsSearchOptionsDTO;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsSearchResultDTO;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class NewsMarketingAnalyticsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['osint.news_media_intel.searxng.available_engines' => [
            'general' => ['google', 'bing'], 'news' => ['google news', 'bing news'],
        ]]);
    }

    public function test_entity_shares_use_the_total_literal_matches_in_one_common_sample(): void
    {
        $result = $this->analyze([
            $this->mention('https://one.example/a', title: 'Мир and Acme growth', snippet: 'МИР repeated Мир'),
            $this->mention('https://two.example/b', title: 'Acme attack'),
            $this->mention('https://three.example/c', title: 'Мировой Acmeology Мирный'),
        ], [], brand: 'Мир', competitors: ['Acme']);

        $comparison = $result['brandComparison'];
        $this->assertSame('entity_matches', $comparison['denominator']);
        $this->assertSame(3, $comparison['totalMatches']);
        $this->assertSame(1, $comparison['coMentionDocuments']);
        $this->assertSame([1, 2], array_column($comparison['entities'], 'mentions'));
        $this->assertSame([33.33, 66.67], array_column($comparison['entities'], 'share'));
        $this->assertSame(['positive' => 1, 'neutral' => 0, 'negative' => 0], $comparison['entities'][0]['sentiment']);
        $this->assertSame(['positive' => 1, 'neutral' => 0, 'negative' => 1], $comparison['entities'][1]['sentiment']);
        $this->assertSame(['https://one.example/a'], $comparison['entities'][0]['evidenceUrls']);
        $this->assertSame(3, $result['summary']['mentions']);
    }

    public function test_multiword_entities_escape_punctuation_and_do_not_match_inside_unicode_words(): void
    {
        $result = $this->analyze([
            $this->mention('https://one.example/a', title: 'A.C.M.E (Lab) uses C++ and Ёлка'),
            $this->mention('https://two.example/b', title: 'AXCXMXE (Lab) C++17 Ёлками'),
        ], [], brand: 'A.C.M.E (Lab)', competitors: ['C++', 'Ёлка', 'ёлка', 'ignored fourth']);

        $this->assertSame([1, 1, 1], array_column($result['brandComparison']['entities'], 'mentions'));
        $this->assertSame(3, $result['brandComparison']['totalMatches']);
    }

    public function test_url_content_bridges_keep_all_category_and_engine_provenance(): void
    {
        $result = $this->analyze([
            $this->mention('https://example.com/first?utm_source=web', '2026-10-05T12:00:00Z', 'Initial report', 'Brief', engines: ['google'], position: 4),
            $this->mention('https://example.net/second', '2026-10-05T12:00:00Z', 'Initial report', 'Long connecting description', engines: ['bing'], position: 8),
        ], [
            $this->mention('https://example.net/second', title: 'Third copy', snippet: 'Another view', engines: ['bing news'], category: 'news'),
            $this->mention('https://example.com/first', '2026-10-05T12:00:00Z', 'Initial report', 'Long connecting description', engines: ['google news'], category: 'news'),
        ]);

        $this->assertCount(1, $result['mentions']);
        $this->assertSame(['general', 'news'], $result['mentions'][0]['categories']);
        $this->assertSame(['google', 'bing', 'bing news', 'google news'], $result['mentions'][0]['engines']);
        $this->assertSame(1, $result['summary']['generalMentions']);
        $this->assertSame(1, $result['summary']['newsMentions']);
        $this->assertSame([['date' => '2026-10-05', 'mentions' => 1]], $result['timeline']);
    }

    public function test_visibility_counts_only_general_results_and_checks_hostname_boundaries(): void
    {
        $result = $this->analyze([
            $this->mention('https://www.example.com/page', position: 7),
            $this->mention('https://shop.example.com/page', position: 3),
            $this->mention('https://evil-example.com/page', position: 1),
            $this->mention('https://example.com.evil.org/page', position: 2),
        ], [
            $this->mention('https://example.com/news', position: 1, category: 'news'),
        ], domain: 'example.com');

        $this->assertSame('observed_general_results', $result['visibility']['method']);
        $this->assertSame(4, $result['visibility']['generalResults']);
        $this->assertSame(2, $result['visibility']['domainMatches']);
        $this->assertSame(50.0, $result['visibility']['share']);
        $this->assertSame(3, $result['visibility']['bestObservedPosition']);
        $this->assertSame(['https://shop.example.com/page', 'https://www.example.com/page'], array_column($result['visibility']['pages'], 'url'));
    }

    public function test_a_richer_news_duplicate_does_not_erase_general_domain_position(): void
    {
        $result = $this->analyze([
            $this->mention('https://example.com/page', title: 'Shared article', snippet: 'Description', position: 12),
        ], [
            $this->mention('https://publisher.net/article', '2026-10-05', 'Shared article', 'Description', category: 'news', position: 1),
        ], domain: 'example.com');

        $this->assertSame('https://publisher.net/article', $result['mentions'][0]['link']);
        $this->assertSame(1, $result['visibility']['domainMatches']);
        $this->assertSame(12, $result['visibility']['bestObservedPosition']);
        $this->assertSame('https://example.com/page', $result['visibility']['pages'][0]['url']);
    }

    public function test_missing_domain_positions_stay_unknown_and_never_become_zero(): void
    {
        $result = $this->analyze([$this->mention('https://example.com/page')], [], domain: 'example.com');

        $this->assertSame(1, $result['visibility']['domainMatches']);
        $this->assertNull($result['visibility']['bestObservedPosition']);
        $this->assertNull($result['visibility']['pages'][0]['position']);
    }

    public function test_real_search_keeps_domain_evidence_when_another_domain_has_identical_content_first(): void
    {
        $result = $this->analyzeSearchResults([
            ['url' => 'https://competitor.com/page', 'title' => 'Shared topic', 'content' => 'The same syndicated description', 'engine' => 'google'],
            ['url' => 'https://example.com/page', 'title' => 'Shared topic', 'content' => 'The same syndicated description', 'engine' => 'bing'],
        ]);

        $this->assertSame(1, $result['summary']['mentions']);
        $this->assertSame('https://competitor.com/page', $result['mentions'][0]['link']);
        $this->assertSame(1, $result['visibility']['domainMatches']);
        $this->assertSame(2, $result['visibility']['bestObservedPosition']);
        $this->assertSame([['url' => 'https://example.com/page', 'title' => 'Shared topic', 'position' => 2]], $result['visibility']['pages']);
        Http::assertSentCount(2);
    }

    public function test_real_search_richer_tracking_alias_keeps_the_first_observed_domain_position(): void
    {
        $result = $this->analyzeSearchResults([
            ['url' => 'https://competitor.com/page', 'title' => 'Competitor article', 'content' => 'Different article', 'engine' => 'google'],
            ['url' => 'https://example.com/page?utm_source=first', 'title' => 'Our article', 'content' => 'Brief description', 'engine' => 'google'],
            ['url' => 'https://www.example.com/page/?utm_source=second', 'title' => 'Our article', 'content' => 'A richer description selected from a second engine', 'engine' => 'bing'],
        ]);

        $this->assertSame(2, $result['summary']['mentions']);
        $this->assertSame(1, $result['visibility']['domainMatches']);
        $this->assertSame(2, $result['visibility']['bestObservedPosition']);
        $this->assertSame(2, $result['visibility']['pages'][0]['position']);
        $this->assertSame('https://www.example.com/page/?utm_source=second', $result['visibility']['pages'][0]['url']);
        $this->assertSame('A richer description selected from a second engine', $result['mentions'][1]['snippet']);
        Http::assertSentCount(2);
    }

    public function test_date_coverage_excludes_future_unknown_and_epoch_dates_and_timeline_uses_only_news(): void
    {
        $result = $this->analyze([
            $this->mention('https://web.example/dated', '2026-10-08T12:00:00Z'),
        ], [
            $this->mention('https://news.example/recent', '2026-10-05T12:00:00Z', category: 'news'),
            $this->mention('https://news.example/old', '2026-09-01T12:00:00Z', category: 'news'),
            $this->mention('https://news.example/future', '2026-10-10T12:00:00Z', category: 'news'),
            $this->mention('https://news.example/unknown', category: 'news'),
            $this->mention('https://news.example/invalid', 'not-a-date', category: 'news'),
            $this->mention('https://news.example/epoch', '1970-01-01', category: 'news'),
        ]);

        $this->assertSame(7, $result['summary']['mentions']);
        $this->assertSame(3, $result['summary']['knownDates']);
        $this->assertSame(3, $result['summary']['undatedMentions']);
        $this->assertSame(1, $result['summary']['futureDates']);
        $this->assertSame(2, $result['summary']['freshMentions']);
        $this->assertSame(66.67, $result['summary']['freshnessPercent']);
        $this->assertSame([['date' => '2026-09-01', 'mentions' => 1], ['date' => '2026-10-05', 'mentions' => 1]], $result['timeline']);
    }

    public function test_an_undated_sample_has_unknown_freshness_and_no_invented_timeline(): void
    {
        $result = $this->analyze([], [$this->mention('https://news.example/a', category: 'news')]);

        $this->assertNull($result['summary']['freshnessPercent']);
        $this->assertSame([], $result['timeline']);
    }

    public function test_topics_and_phrases_count_documents_instead_of_repeated_tokens(): void
    {
        $result = $this->analyze([
            $this->mention('https://one.example/a', title: 'Content strategy content strategy content strategy', snippet: 'Content content content'),
            $this->mention('https://two.example/b', title: 'Content strategy examples'),
        ], []);
        $topics = array_column($result['topics'], null, 'topic');

        $this->assertSame(2, $topics['content']['count']);
        $this->assertSame(100.0, $topics['content']['share']);
        $this->assertSame(2, $topics['content strategy']['count']);
        $this->assertCount(2, $topics['content strategy']['evidenceUrls']);
        $this->assertSame(1, $topics['examples']['count']);
        $opportunity = array_values(array_filter($result['contentOpportunities'], static fn ($item) => $item['code'] === 'topic_coverage' && $item['params']['topic'] === 'content strategy'))[0];
        $this->assertSame(['topic' => 'content strategy', 'documents' => 2, 'share' => 100.0], $opportunity['params']);
    }

    public function test_questions_merge_duplicate_sentences_and_suggestions_without_inventing_demand(): void
    {
        $general = new NewsSearchResultDTO([
            $this->mention('https://one.example/a', title: 'How to improve content?', snippet: 'How to improve content? How to improve content?'),
            $this->mention('https://two.example/b', title: 'Another guide', snippet: 'How to improve content?'),
        ], [], ['HOW TO IMPROVE CONTENT?', 'Why audit a website', 'More content ideas']);
        $result = $this->analyze($general, []);
        $questions = array_column($result['questions'], null, 'question');

        $this->assertCount(2, $questions);
        $this->assertSame(2, $questions['How to improve content?']['count']);
        $this->assertSame(['result', 'suggestion'], $questions['How to improve content?']['sources']);
        $this->assertSame(0, $questions['Why audit a website']['count']);
        $this->assertSame([], $questions['Why audit a website']['evidenceUrls']);
        $this->assertSame(['suggestion'], $questions['Why audit a website']['sources']);
    }

    public function test_publishers_merge_www_hosts_and_include_evidence_and_source_categories(): void
    {
        $result = $this->analyze([
            $this->mention('https://www.publisher.com/a', engines: ['google']),
        ], [
            $this->mention('https://PUBLISHER.com/b', engines: ['bing news'], category: 'news'),
            $this->mention('https://other.net/c', category: 'news'),
        ]);

        $this->assertSame(2, $result['summary']['publishers']);
        $this->assertSame('publisher.com', $result['publishers'][0]['host']);
        $this->assertSame(2, $result['publishers'][0]['count']);
        $this->assertSame(66.67, $result['publishers'][0]['share']);
        $this->assertSame(['general', 'news'], $result['publishers'][0]['types']);
        $this->assertSame(['google', 'bing news'], $result['publishers'][0]['engines']);
        $this->assertCount(2, $result['publishers'][0]['evidenceUrls']);
    }

    public function test_suggestions_and_auxiliary_search_data_are_deduplicated_across_categories(): void
    {
        $general = new NewsSearchResultDTO([], [], ['SEO content'], ['Search spelling'], ['Direct answer'], [['title' => 'Entity', 'content' => 'Facts']]);
        $news = new NewsSearchResultDTO([], [], ['seo content', 'More content'], ['search spelling'], ['Direct answer'], [['title' => 'Entity', 'content' => 'Facts']]);
        $result = $this->analyze($general, $news);

        $this->assertSame(['SEO content', 'More content'], $result['suggestions']);
        $this->assertSame(['Search spelling'], $result['corrections']);
        $this->assertSame(['Direct answer'], $result['answers']);
        $this->assertSame([['title' => 'Entity', 'content' => 'Facts']], $result['infoboxes']);
        $this->assertSame([], $result['contentOpportunities']);
        $this->assertFalse($result['coverage']['partial']);
    }

    public function test_an_unavailable_category_returns_the_other_sample_with_explicit_partial_coverage(): void
    {
        $failure = new ExternalServiceUnavailableException('errors.news_media_intel.unavailable', 'news_search_unavailable');
        $result = $this->analyze($failure, [$this->mention('https://news.example/a', category: 'news')]);

        $this->assertTrue($result['coverage']['partial']);
        $this->assertSame('unavailable', $result['coverage']['general']['status']);
        $this->assertSame(0, $result['coverage']['general']['pagesRequested']);
        $this->assertSame(0, $result['coverage']['general']['pagesLoaded']);
        $this->assertSame([], $result['coverage']['general']['engines']);
        $this->assertSame([], $result['coverage']['general']['unresponsiveEngines']);
        $this->assertNull($result['coverage']['general']['estimatedTotal']);
        $this->assertSame(['general'], $result['coverage']['general']['categories']);
        $this->assertSame('ok', $result['coverage']['news']['status']);
        $this->assertSame(1, $result['summary']['mentions']);
    }

    public function test_both_unavailable_categories_fail_instead_of_presenting_an_empty_success(): void
    {
        $failure = new ExternalServiceUnavailableException('errors.news_media_intel.unavailable', 'news_search_unavailable');
        $this->expectException(ExternalServiceUnavailableException::class);

        $this->analyze($failure, $failure);
    }

    public function test_programming_errors_are_not_hidden_as_partial_external_failures(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unexpected failure');

        $this->analyze(new RuntimeException('Unexpected failure'), []);
    }

    public function test_truncated_upstream_pages_mark_the_combined_result_as_partial(): void
    {
        $result = $this->analyze(new NewsSearchResultDTO([], ['truncated' => true, 'stopReason' => 'deadline', 'pagesLoaded' => 1]), []);

        $this->assertTrue($result['coverage']['partial']);
        $this->assertSame('deadline', $result['coverage']['general']['stopReason']);
        $this->assertSame(1, $result['coverage']['general']['pagesLoaded']);
    }

    public function test_the_sample_limit_reserves_results_from_both_categories(): void
    {
        config(['osint.news_media_intel.service.max_mentions' => 3]);
        $result = $this->analyze([
            $this->mention('https://web.example/a'), $this->mention('https://web.example/b'), $this->mention('https://web.example/c'),
        ], [
            $this->mention('https://news.example/a', category: 'news'), $this->mention('https://news.example/b', category: 'news'),
        ]);

        $this->assertSame(3, $result['summary']['mentions']);
        $this->assertSame(2, $result['summary']['generalMentions']);
        $this->assertSame(1, $result['summary']['newsMentions']);
        $this->assertTrue($result['coverage']['analyticsSampleTruncated']);
        $this->assertTrue($result['coverage']['partial']);
    }

    public function test_search_options_filter_engines_per_category_and_share_one_bounded_deadline(): void
    {
        config(['osint.news_media_intel.searxng.request_budget_seconds' => 500]);
        $calls = [];
        $this->mock(SearxngSearchClientInterface::class, function ($mock) use (&$calls): void {
            $mock->shouldReceive('search')->twice()->andReturnUsing(function ($query, $options, $deadline) use (&$calls) {
                $calls[] = ['query' => $query, 'options' => $options, 'deadline' => $deadline];

                return new NewsSearchResultDTO([], []);
            });
        });
        $before = microtime(true);
        app(NewsMarketingAnalyticsService::class)->analyze(new NewsMarketingLookupDTO(' shared query ',
            new NewsSearchOptionsDTO(language: 'ru-RU', timeRange: 'month', safeSearch: 2, engines: ['google', 'bing news'], maxPages: 4)));

        $this->assertSame(['general'], $calls[0]['options']->categories);
        $this->assertSame(['google'], $calls[0]['options']->engines);
        $this->assertSame(['news'], $calls[1]['options']->categories);
        $this->assertSame(['bing news'], $calls[1]['options']->engines);
        $this->assertSame('shared query', $calls[0]['query']);
        $this->assertSame('shared query', $calls[1]['query']);
        $this->assertSame('ru-RU', $calls[1]['options']->language);
        $this->assertSame('month', $calls[1]['options']->timeRange);
        $this->assertSame(2, $calls[1]['options']->safeSearch);
        $this->assertSame(4, $calls[1]['options']->maxPages);
        $this->assertEqualsWithDelta(12.5, $calls[0]['deadline'] - $before, 0.1);
        $this->assertEqualsWithDelta(25.0, $calls[1]['deadline'] - $before, 0.1);
    }

    public function test_explicit_engines_never_fall_back_to_unselected_category_defaults(): void
    {
        $this->mock(SearxngSearchClientInterface::class, function ($mock): void {
            $mock->shouldReceive('search')->once()->withArgs(fn ($query, $options, $deadline) => $options->categories === ['news'] && $options->engines === ['bing news'])
                ->andReturn(new NewsSearchResultDTO([], []));
        });
        $result = app(NewsMarketingAnalyticsService::class)->analyze(new NewsMarketingLookupDTO('query', new NewsSearchOptionsDTO(engines: ['bing news'])));

        $this->assertSame('no_selected_engines', $result['coverage']['general']['stopReason']);
        $this->assertSame(0, $result['coverage']['general']['pagesLoaded']);
        $this->assertSame([], $result['coverage']['general']['engines']);
        $this->assertSame([], $result['coverage']['general']['unresponsiveEngines']);
        $this->assertTrue($result['coverage']['partial']);
    }

    public function test_result_options_describe_effective_search_settings_and_both_categories(): void
    {
        config(['osint.news_media_intel.searxng.safe_search' => 2]);
        $result = $this->analyze(
            new NewsSearchResultDTO([], ['language' => 'en-US', 'timeRange' => 'week', 'maxPages' => 4, 'requestedEngines' => ['google']]),
            new NewsSearchResultDTO([], ['language' => 'en-US', 'timeRange' => 'week', 'maxPages' => 4, 'requestedEngines' => ['bing news']]),
        );

        $this->assertSame(['general', 'news'], $result['options']['categories']);
        $this->assertSame('en-US', $result['options']['language']);
        $this->assertSame('week', $result['options']['timeRange']);
        $this->assertSame(2, $result['options']['safeSearch']);
        $this->assertSame(4, $result['options']['maxPages']);
        $this->assertSame(['google', 'bing news'], $result['options']['engines']);
    }

    public function test_zero_brand_matches_create_a_sample_scoped_opportunity(): void
    {
        $result = $this->analyze([$this->mention('https://publisher.net/a')], [], brand: 'Absent Brand');
        $opportunities = array_values(array_filter($result['contentOpportunities'], static fn ($row) => $row['code'] === 'brand_presence'));

        $this->assertCount(1, $opportunities);
        $this->assertSame(['brand' => 'Absent Brand', 'sampleSize' => 1], $opportunities[0]['params']);
        $this->assertSame(0.0, $result['brandComparison']['entities'][0]['share']);
    }

    private function mention(string $link, string $publishedAt = '', ?string $title = null, string $snippet = '', array $engines = [], string $category = 'general', ?int $position = null): NewsMentionDTO
    {
        return new NewsMentionDTO(source: 'searxng', title: $title ?? $link, snippet: $snippet, link: $link,
            publishedAt: $publishedAt, engines: $engines, category: $category, position: $position);
    }

    private function analyzeSearchResults(array $generalResults): array
    {
        config(['osint.news_media_intel.searxng.base_url' => 'http://searx.test', 'osint.news_media_intel.searxng.engines' => []]);
        Http::preventStrayRequests();
        Http::fake(['http://searx.test/search' => fn (Request $request) => Http::response([
            'results' => $request['categories'] === 'general' ? $generalResults : [],
        ])]);

        return app(NewsMarketingAnalyticsService::class)->analyze(new NewsMarketingLookupDTO('content strategy',
            new NewsSearchOptionsDTO(maxPages: 1), domain: 'example.com'));
    }

    private function analyze(array|NewsSearchResultDTO|\Throwable $general, array|NewsSearchResultDTO|\Throwable $news, string $brand = '', array $competitors = [], string $domain = ''): array
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-09T12:00:00Z'));
        $responses = ['general' => $general, 'news' => $news];
        $this->mock(SearxngSearchClientInterface::class, function ($mock) use ($responses): void {
            $count = $responses['general'] instanceof \Throwable && ! $responses['general'] instanceof ExternalServiceUnavailableException ? 1 : 2;
            $mock->shouldReceive('search')->times($count)->andReturnUsing(function ($query, $options, $deadline) use ($responses) {
                $response = $responses[$options->categories[0]];
                if ($response instanceof \Throwable) {
                    throw $response;
                }

                return $response instanceof NewsSearchResultDTO ? $response : new NewsSearchResultDTO($response, ['truncated' => false, 'pagesLoaded' => 1]);
            });
        });

        return app(NewsMarketingAnalyticsService::class)->analyze(new NewsMarketingLookupDTO('common query', new NewsSearchOptionsDTO, $brand, $competitors, $domain));
    }
}
