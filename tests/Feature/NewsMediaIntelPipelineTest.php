<?php

namespace Tests\Feature;

use App\Modules\NewsMediaIntel\Application\Contracts\NewsFeedFetcherInterface;
use App\Modules\NewsMediaIntel\Application\Contracts\NewsMediaIntelServiceInterface;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsMediaIntelLookupDTO;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsMentionDTO;
use Tests\TestCase;

class NewsMediaIntelPipelineTest extends TestCase
{
    public function test_a_better_snippet_replaces_the_same_article_once_in_mentions_and_analytics(): void
    {
        $result = $this->monitor([
            $this->mention('https://example.com/article?utm_source=first', '2026-10-05T12:00:00Z'),
            $this->mention('https://www.example.com/article/', '2026-10-05T12:00:00Z', snippet: 'A more detailed description of the article'),
        ]);

        $this->assertCount(1, $result['mentions']);
        $this->assertSame('A more detailed description of the article', $result['mentions'][0]['snippet']);
        $this->assertSame(['positive' => 0, 'neutral' => 1, 'negative' => 0], $result['sentiment']);
        $this->assertSame([['date' => '2026-10-05', 'mentions' => 1]], $result['timeline']);
    }

    public function test_a_known_publication_date_replaces_an_undated_article_even_with_a_shorter_snippet(): void
    {
        $result = $this->monitor([
            $this->mention('https://example.com/article', snippet: 'A long description without a known publication date'),
            $this->mention('https://example.com/article', '2026-10-05T12:00:00Z'),
        ]);

        $this->assertCount(1, $result['mentions']);
        $this->assertSame('2026-10-05T12:00:00Z', $result['mentions'][0]['publishedAt']);
        $this->assertSame('Brief', $result['mentions'][0]['snippet']);
        $this->assertSame([['date' => '2026-10-05', 'mentions' => 1]], $result['timeline']);
    }

    public function test_an_undated_duplicate_does_not_erase_a_known_publication_date(): void
    {
        $result = $this->monitor([
            $this->mention('https://example.com/article', '2026-10-05T12:00:00Z'),
            $this->mention('https://example.com/article', snippet: 'A longer description without a known publication date'),
        ]);

        $this->assertCount(1, $result['mentions']);
        $this->assertSame('2026-10-05T12:00:00Z', $result['mentions'][0]['publishedAt']);
        $this->assertSame('Brief', $result['mentions'][0]['snippet']);
    }

    public function test_the_same_content_at_another_url_has_one_best_mention(): void
    {
        $result = $this->monitor([
            $this->mention('https://example.com/first'),
            $this->mention('https://example.net/second', '2026-10-05T12:00:00Z'),
        ]);

        $this->assertCount(1, $result['mentions']);
        $this->assertSame('https://example.net/second', $result['mentions'][0]['link']);
        $this->assertSame([['date' => '2026-10-05', 'mentions' => 1]], $result['timeline']);
    }

    public function test_an_unchosen_alternate_url_still_identifies_later_duplicates(): void
    {
        $result = $this->monitor([
            $this->mention('https://example.com/first', '2026-10-05T12:00:00Z'),
            $this->mention('https://example.net/alternate'),
            $this->mention('https://example.net/alternate', '2026-10-05T12:00:00Z', snippet: 'A better description on the alternate URL'),
        ]);

        $this->assertCount(1, $result['mentions']);
        $this->assertSame('A better description on the alternate URL', $result['mentions'][0]['snippet']);
    }

    public function test_a_url_content_bridge_merges_existing_groups_without_changing_first_seen_order(): void
    {
        $result = $this->monitor([
            $this->mention('https://example.com/first', '2026-10-05T12:00:00Z'),
            $this->mention('https://example.org/unrelated', '2026-10-05T12:00:00Z', title: 'Unrelated report'),
            $this->mention('https://example.net/second', '2026-10-05T12:00:00Z', snippet: 'A detailed description connecting both copies'),
            $this->mention('https://example.com/first', '2026-10-05T12:00:00Z', snippet: 'A detailed description connecting both copies'),
        ]);

        $this->assertSame(['https://example.net/second', 'https://example.org/unrelated'], array_column($result['mentions'], 'link'));
        $this->assertSame(['positive' => 0, 'neutral' => 2, 'negative' => 0], $result['sentiment']);
        $this->assertSame([['date' => '2026-10-05', 'mentions' => 2]], $result['timeline']);
    }

    public function test_undated_or_invalidly_dated_mentions_do_not_invent_a_publication_day(): void
    {
        $result = $this->monitor([
            $this->mention('https://example.com/unknown', title: 'Unknown report'),
            $this->mention('https://example.com/invalid', 'not-a-date', title: 'Invalid report'),
            $this->mention('https://example.com/epoch', '1970-01-01', title: 'Epoch report'),
        ]);

        $this->assertCount(3, $result['mentions']);
        $this->assertSame([], $result['timeline']);
        $this->assertSame(['positive' => 0, 'neutral' => 3, 'negative' => 0], $result['sentiment']);
    }

    public function test_timeline_counts_only_known_dates_and_sorts_days(): void
    {
        $result = $this->monitor([
            $this->mention('https://example.com/later', '2026-10-06T12:00:00Z', title: 'Later report'),
            $this->mention('https://example.com/earlier', '2026-10-05T12:00:00Z', title: 'Earlier report'),
            $this->mention('https://example.com/same-day', '2026-10-05T14:00:00Z', title: 'Another report'),
            $this->mention('https://example.com/unknown', title: 'Undated report'),
        ]);

        $this->assertCount(4, $result['mentions']);
        $this->assertSame([
            ['date' => '2026-10-05', 'mentions' => 2],
            ['date' => '2026-10-06', 'mentions' => 1],
        ], $result['timeline']);
    }

    private function mention(string $link, string $publishedAt = '', string $title = 'Acme report', string $snippet = 'Brief'): NewsMentionDTO
    {
        return new NewsMentionDTO(source: 'searxng', title: $title, snippet: $snippet, link: $link, publishedAt: $publishedAt);
    }

    /** @param array<int, NewsMentionDTO> $mentions */
    private function monitor(array $mentions): array
    {
        $this->mock(NewsFeedFetcherInterface::class, function ($mock) use ($mentions): void {
            $mock->shouldReceive('fetchAll')->once()->with('acme')->andReturn($mentions);
        });

        return app(NewsMediaIntelServiceInterface::class)->monitor(new NewsMediaIntelLookupDTO('acme'))->toArray();
    }
}
