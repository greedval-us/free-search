<?php

namespace Tests\Feature;

use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel\NewsMentionDeduplicator;
use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel\NewsMentionFingerprintFactory;
use App\Modules\NewsMediaIntel\Application\Support\NewsMediaIntelConfig;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsMentionDTO;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsSearchOptionsDTO;
use App\Modules\NewsMediaIntel\Infrastructure\Feeds\SearxngSearchClient;
use App\Support\Observability\ExternalServiceLogger;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SearxngSearchClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    public function test_explicit_engines_cannot_expand_to_all_engines_of_a_category(): void
    {
        Http::fake(['http://127.0.0.1:8088/search' => Http::response(['results' => []])]);

        $result = $this->client()->search('acme site:example.com', new NewsSearchOptionsDTO(
            categories: ['general'], language: 'en', timeRange: 'week', safeSearch: 2, engines: [' GOOGLE ', 'bing'], maxPages: 1,
        ));

        Http::assertSent(fn (Request $request): bool => $request['q'] === 'acme site:example.com'
            && $request['engines'] === 'google,bing' && ! isset($request['categories'])
            && $request['language'] === 'en' && $request['time_range'] === 'week' && $request['safesearch'] === 2);
        $this->assertSame(['google', 'bing'], $result->coverage['requestedEngines']);
        $this->assertSame('exhausted', $result->coverage['stopReason']);
        $this->assertFalse($result->coverage['truncated']);
    }

    public function test_all_search_uses_news_and_general_and_preserves_observed_order_and_provenance(): void
    {
        Http::fake(['http://127.0.0.1:8088/search' => Http::sequence()
            ->push(['results' => [
                $this->article('a', ['engines' => ['google', 'bing'], 'positions' => [9, 1], 'category' => 'general']),
                $this->article('b', ['engine' => 'google news', 'category' => 'news', 'publisher' => '<b>Acme Press</b>']),
            ]])->push(['results' => []])]);

        $result = $this->client()->search('acme', new NewsSearchOptionsDTO(categories: ['all']));

        Http::assertSent(fn (Request $request): bool => $request['categories'] === 'news,general' && ! isset($request['engines']));
        $this->assertSame([1, 2], array_column($result->mentions, 'position'));
        $this->assertSame(['bing', 'google'], $result->mentions[0]->engines);
        $this->assertSame('general', $result->mentions[0]->category);
        $this->assertSame(['general'], $result->mentions[0]->categories);
        $this->assertSame('Acme Press', $result->mentions[1]->publisher);
        $this->assertSame(['bing', 'google', 'google news'], $result->coverage['engines']);
        $this->assertSame(2, $result->coverage['pagesRequested']);
        $this->assertSame(2, $result->coverage['pagesLoaded']);
        $this->assertNull($result->coverage['estimatedTotal']);
        $this->assertFalse($result->coverage['truncated']);
        Http::assertSentCount(2);
    }

    public function test_default_engine_preferences_are_filtered_for_each_category(): void
    {
        Http::fake(['http://127.0.0.1:8088/search' => Http::response(['results' => []])]);
        $client = $this->client(['engines' => ['google news', 'bing news']]);

        $client->search('acme', new NewsSearchOptionsDTO(categories: ['general']));
        $client->search('acme', new NewsSearchOptionsDTO(categories: ['news']));

        Http::assertSent(fn (Request $request): bool => isset($request['categories']) && $request['categories'] === 'general' && ! isset($request['engines']));
        Http::assertSent(fn (Request $request): bool => isset($request['engines']) && $request['engines'] === 'google news,bing news' && ! isset($request['categories']));
        Http::assertSentCount(2);
    }

    public function test_an_explicit_all_time_filter_can_clear_the_configured_default_period(): void
    {
        Http::fake(['http://127.0.0.1:8088/search' => Http::response(['results' => []])]);
        $client = $this->client(['time_range' => 'month']);

        $default = $client->search('configured-default');
        $allTime = $client->search('all-time', new NewsSearchOptionsDTO(timeRange: ''));

        Http::assertSent(fn (Request $request): bool => $request['q'] === 'configured-default' && $request['time_range'] === 'month');
        Http::assertSent(fn (Request $request): bool => $request['q'] === 'all-time' && ! isset($request['time_range']));
        $this->assertSame('month', $default->coverage['timeRange']);
        $this->assertSame('', $allTime->coverage['timeRange']);
        Http::assertSentCount(2);
    }

    public function test_richer_duplicates_retain_all_engines_categories_and_first_observed_position(): void
    {
        Http::fake(['http://127.0.0.1:8088/search' => Http::sequence()
            ->push(['results' => [$this->article('a', ['engine' => 'google', 'category' => 'general'])]])
            ->push(['results' => [$this->article('a', ['content' => 'Detailed research and useful update',
                'engine' => 'bing news', 'category' => 'news', 'publishedDate' => '2026-10-09T12:00:00Z'])]])
            ->push(['results' => []])]);

        $result = $this->client()->search('acme', new NewsSearchOptionsDTO(categories: ['all']));

        $this->assertCount(1, $result->mentions);
        $this->assertSame('Detailed research and useful update', $result->mentions[0]->snippet);
        $this->assertSame(['bing news', 'google'], $result->mentions[0]->engines);
        $this->assertSame(['general', 'news'], $result->mentions[0]->categories);
        $this->assertSame('mixed', $result->mentions[0]->category);
        $this->assertSame(1, $result->mentions[0]->position);
        Http::assertSentCount(3);
    }

    public function test_url_content_bridges_keep_provenance_of_every_joined_group(): void
    {
        $config = NewsMediaIntelConfig::fromArray([]);
        $deduplicator = new NewsMentionDeduplicator(new NewsMentionFingerprintFactory($config));
        $first = new NewsMentionDTO('searxng', 'Acme', 'Short', 'https://example.com/a', '', ['google'], 'general', 1);
        $second = new NewsMentionDTO('searxng', 'Other', 'Rich summary', 'https://example.org/b', '', ['bing news'], 'news', 2);
        $bridge = new NewsMentionDTO('searxng', 'Other', 'Rich summary', 'https://example.com/a', '', ['brave'], 'general', 3);
        $laterAlias = new NewsMentionDTO('searxng', 'Update', 'A longer accurate summary of Acme', 'https://example.org/b', '', ['duckduckgo'], 'general', 4);

        $mentions = $deduplicator->deduplicate([$first, $second, $bridge, $laterAlias]);

        $this->assertCount(1, $mentions);
        $this->assertSame('A longer accurate summary of Acme', $mentions[0]->snippet);
        $this->assertSame(['bing news', 'brave', 'duckduckgo', 'google'], $mentions[0]->engines);
        $this->assertSame(['general', 'news'], $mentions[0]->categories);
        $this->assertSame(1, $mentions[0]->position);
    }

    #[DataProvider('webCategories')]
    public function test_web_pages_with_identical_text_keep_each_domain_and_its_own_observed_ordinal(array $categories): void
    {
        Http::fake(['http://127.0.0.1:8088/search' => Http::response(['results' => [
            $this->article('competitor', ['url' => 'https://competitor.com/page', 'title' => 'Shared article', 'content' => 'Identical text', 'category' => 'general']),
            $this->article('brand', ['url' => 'https://acme.com/page', 'title' => 'Shared article', 'content' => 'Identical text', 'category' => 'general']),
        ]])]);

        $result = $this->client()->search('acme', new NewsSearchOptionsDTO(categories: $categories, maxPages: 1));

        $this->assertCount(2, $result->mentions);
        $this->assertSame(['https://competitor.com/page', 'https://acme.com/page'], array_column($result->mentions, 'link'));
        $this->assertSame([1, 2], array_column($result->mentions, 'position'));
        Http::assertSentCount(1);
    }

    public static function webCategories(): array
    {
        return ['general only' => [['general']], 'combined search' => [['general', 'news']]];
    }

    public function test_auxiliary_metadata_is_plain_bounded_and_has_only_safe_links(): void
    {
        Http::fake(['http://127.0.0.1:8088/search' => Http::response([
            'results' => [],
            'suggestions' => ['<b>Acme</b> growth', 'Acme growth', ['bad' => 'shape'], ...array_fill(0, 25, 'related')],
            'corrections' => ['&lt;strong&gt;corrected&lt;/strong&gt;', null],
            'answers' => [['answer' => '<i>Useful answer</i>', 'url' => 'javascript:alert(1)'], 'Another answer', ['answer' => ['invalid']]],
            'infoboxes' => [[
                'infobox' => '<h1>Acme</h1>', 'content' => '&lt;p&gt;Company profile&lt;/p&gt;',
                'urls' => [['title' => '<b>Official</b>', 'url' => 'https://example.com/'],
                    ['title' => 'Unsafe', 'url' => 'javascript:alert(1)'], ['url' => 'https://user:pass@example.com/']],
                'attributes' => [['label' => '<b>Industry</b>', 'value' => '<i>Research</i>']],
            ]],
        ])]);

        $result = $this->client()->search('acme');

        $this->assertSame(['Acme growth', 'related'], $result->suggestions);
        $this->assertSame(['corrected'], $result->corrections);
        $this->assertSame(['Useful answer', 'Another answer'], $result->answers);
        $this->assertSame('Acme', $result->infoboxes[0]['title']);
        $this->assertSame('Company profile', $result->infoboxes[0]['content']);
        $this->assertSame([['title' => 'Official', 'url' => 'https://example.com/']], $result->infoboxes[0]['urls']);
        $this->assertSame([['label' => 'Industry', 'value' => 'Research']], $result->infoboxes[0]['attributes']);
        Http::assertSentCount(1);
    }

    public function test_auxiliary_lists_and_texts_have_hard_limits(): void
    {
        Http::fake(['http://127.0.0.1:8088/search' => Http::response([
            'results' => [], 'suggestions' => array_map(fn (int $i): string => $i.str_repeat('a', 600), range(1, 40)),
            'corrections' => array_map(fn (int $i): string => 'correction '.$i, range(1, 40)),
            'answers' => array_map(fn (int $i): array => ['answer' => $i.str_repeat('b', 3000)], range(1, 30)),
            'infoboxes' => array_map(fn (int $i): array => ['infobox' => 'Company '.$i], range(1, 20)),
        ])]);

        $result = $this->client()->search('acme');

        $this->assertCount(20, $result->suggestions);
        $this->assertSame(500, mb_strlen($result->suggestions[0]));
        $this->assertCount(20, $result->corrections);
        $this->assertCount(10, $result->answers);
        $this->assertSame(2000, mb_strlen($result->answers[0]));
        $this->assertCount(5, $result->infoboxes);
        Http::assertSentCount(1);
    }

    public function test_successful_results_with_unresponsive_engines_are_marked_partial(): void
    {
        Http::fake(['http://127.0.0.1:8088/search' => Http::sequence()
            ->push(['results' => [$this->article('a')], 'unresponsive_engines' => [['<b>bing news</b>', 'timeout'], 'invalid']])
            ->push(['results' => []])]);

        $result = $this->client()->search('acme');

        $this->assertCount(1, $result->mentions);
        $this->assertTrue($result->coverage['truncated']);
        $this->assertSame([['name' => 'bing news', 'reason' => 'timeout']], $result->coverage['unresponsiveEngines']);
        $this->assertSame('engines_unavailable', $result->coverage['stopReason']);
        Http::assertSentCount(2);
    }

    #[DataProvider('partialFailures')]
    public function test_a_later_failed_page_retains_results_and_explains_incomplete_coverage(mixed $body, int $status, string $reason): void
    {
        Http::fake(['http://127.0.0.1:8088/search' => Http::sequence()
            ->push(['results' => [$this->article('a')]])->push($body, $status)]);

        $result = $this->client()->search('acme');

        $this->assertCount(1, $result->mentions);
        $this->assertSame(2, $result->coverage['pagesRequested']);
        $this->assertSame(1, $result->coverage['pagesLoaded']);
        $this->assertSame($reason, $result->coverage['stopReason']);
        $this->assertTrue($result->coverage['truncated']);
        Http::assertSentCount(2);
    }

    public static function partialFailures(): array
    {
        return [
            'HTTP failure' => [[], 503, 'http_error'],
            'redirect is not followed' => [[], 302, 'http_error'],
            'invalid JSON' => ['<html>Blocked</html>', 200, 'invalid_response'],
            'unexpected shape' => [['data' => []], 200, 'invalid_response'],
        ];
    }

    public function test_a_later_connection_failure_keeps_prior_results(): void
    {
        Http::fake(['http://127.0.0.1:8088/search' => Http::sequence()
            ->push(['results' => [$this->article('a')]])->pushFailedConnection()]);

        $result = $this->client()->search('acme');

        $this->assertCount(1, $result->mentions);
        $this->assertSame('connection_error', $result->coverage['stopReason']);
        $this->assertTrue($result->coverage['truncated']);
        Http::assertSentCount(2);
    }

    public function test_an_exhausted_shared_deadline_stops_before_contacting_the_search_service(): void
    {
        Http::fake(['http://127.0.0.1:8088/search' => Http::response(['results' => []])]);

        $result = $this->client()->search('acme', deadline: microtime(true) - 1);

        $this->assertSame([], $result->mentions);
        $this->assertSame('deadline', $result->coverage['stopReason']);
        $this->assertSame(0, $result->coverage['pagesRequested']);
        $this->assertTrue($result->coverage['truncated']);
        Http::assertNothingSent();
    }

    public function test_result_and_page_limits_and_repeated_pages_are_explained_in_coverage(): void
    {
        Http::fake(['http://127.0.0.1:8088/search' => Http::response(['results' => [$this->article('a')]])]);

        $limited = $this->client(limit: 1)->search('acme');
        $onePage = $this->client()->search('acme', new NewsSearchOptionsDTO(maxPages: 1));
        $repeated = $this->client()->search('acme');

        $this->assertSame('result_limit', $limited->coverage['stopReason']);
        $this->assertSame('page_limit', $onePage->coverage['stopReason']);
        $this->assertSame('repeated_page', $repeated->coverage['stopReason']);
        $this->assertSame(2, $repeated->coverage['pagesRequested']);
        $this->assertCount(1, $repeated->mentions);
        Http::assertSentCount(4);
    }

    #[DataProvider('invalidQueries')]
    public function test_queries_cannot_override_structured_filters_or_redirect(string $query): void
    {
        Http::fake(['http://127.0.0.1:8088/search' => Http::response(['results' => []])]);

        try {
            $this->client()->search($query);
            $this->fail('Expected a query validation error.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('query', $exception->errors());
        }
        Http::assertNothingSent();
    }

    public static function invalidQueries(): array
    {
        return [['!google acme'], ['acme !!'], [':en acme'], ["acme\nresearch"], ["acme\0"]];
    }

    #[DataProvider('invalidOptions')]
    public function test_direct_client_calls_cannot_bypass_filter_validation(NewsSearchOptionsDTO $options, string $field): void
    {
        Http::fake(['http://127.0.0.1:8088/search' => Http::response(['results' => []])]);

        try {
            $this->client()->search('acme', $options);
            $this->fail('Expected an options validation error.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
        Http::assertNothingSent();
    }

    public static function invalidOptions(): array
    {
        return [
            'unknown category' => [new NewsSearchOptionsDTO(categories: ['images']), 'categories'],
            'category object' => [new NewsSearchOptionsDTO(categories: [['news']]), 'categories'],
            'unknown engine' => [new NewsSearchOptionsDTO(engines: ['intranet']), 'engines'],
            'wrong category engine' => [new NewsSearchOptionsDTO(categories: ['general'], engines: ['bing news']), 'engines'],
            'invalid language' => [new NewsSearchOptionsDTO(language: "ru\n!google"), 'language'],
            'invalid time' => [new NewsSearchOptionsDTO(timeRange: 'century'), 'timeRange'],
            'invalid safety' => [new NewsSearchOptionsDTO(safeSearch: 9), 'safeSearch'],
        ];
    }

    private function client(array $preferences = [], int $limit = 120): SearxngSearchClient
    {
        $config = NewsMediaIntelConfig::fromArray(['searxng' => $preferences, 'service' => ['max_mentions' => $limit]]);

        return new SearxngSearchClient($config, app(ExternalServiceLogger::class),
            new NewsMentionDeduplicator(new NewsMentionFingerprintFactory($config)));
    }

    private function article(string $id, array $overrides = []): array
    {
        return [...['url' => 'https://example.com/'.$id, 'title' => 'Research '.$id, 'content' => 'Summary '.$id], ...$overrides];
    }
}
