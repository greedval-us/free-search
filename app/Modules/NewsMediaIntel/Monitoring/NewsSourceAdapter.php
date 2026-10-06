<?php

namespace App\Modules\NewsMediaIntel\Monitoring;

use App\Models\MonitoringSource;
use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel\NewsMentionFingerprintFactory;
use App\Modules\NewsMediaIntel\Application\Support\NewsMediaIntelConfig;
use App\Services\Monitoring\AdapterSupport;
use App\Services\Monitoring\CollectionPage;
use App\Services\Monitoring\Contracts\SourceAdapter;
use App\Services\Monitoring\SourceUnavailable;
use Carbon\CarbonImmutable;

final readonly class NewsSourceAdapter implements SourceAdapter
{
    public function __construct(private NewsPageFetcher $fetcher, private NewsMediaIntelConfig $config,
        private NewsMentionFingerprintFactory $fingerprints) {}

    public function resolve(string $input): array
    {
        $query = trim(preg_replace('/\s+/u', ' ', $input) ?? '');
        if (mb_strlen($query) < 2 || mb_strlen($query) > 1000 || preg_match('/\p{Cc}/u', $query) !== 0) {
            throw new SourceUnavailable('invalid_source');
        }
        preg_match_all('/(?:^|[\s(])site:([^\s)]+)/iu', $query, $matches);
        $domains = [];
        foreach ($matches[1] ?? [] as $domain) {
            $domain = mb_strtolower($domain);
            if (! $this->publicDomain($domain)) {
                throw new SourceUnavailable('invalid_news_domain');
            }
            $domains[] = $domain;
        }
        $domains = array_values(array_unique($domains));
        sort($domains);
        $configuration = ['query' => $query, 'domains' => $domains, 'language' => $this->config->searxngLanguage()];

        return AdapterSupport::request('news', function () use ($configuration, $query): array {
            // Verify the configured integration; no article URLs or supplied domains are fetched.
            $this->fetcher->fetchPage($query, 1, $configuration['language']);

            return ['identity' => hash('sha256', json_encode($configuration, JSON_THROW_ON_ERROR)),
                'title' => mb_substr($query, 0, 255), 'configuration' => $configuration];
        });
    }

    public function fetch(MonitoringSource $source, CarbonImmutable $from, CarbonImmutable $until, ?string $cursor): CollectionPage
    {
        $configuration = $source->configuration;
        if (! is_string($configuration['query'] ?? null) || ! is_array($configuration['domains'] ?? null)) {
            throw new SourceUnavailable('invalid_source');
        }
        $state = $cursor === null ? ['page' => 1] : json_decode($cursor, true);
        if (! is_array($state) || ! is_int($state['page'] ?? null) || $state['page'] < 1
            || $state['page'] > $this->config->searxngMaxPages()) {
            throw new SourceUnavailable('invalid_cursor');
        }

        return AdapterSupport::request('news', function () use ($configuration, $from, $until, $state): CollectionPage {
            $page = $this->fetcher->fetchPage($configuration['query'], $state['page'], $configuration['language'] ?? null);
            $warnings = $page->warnings;
            $items = [];
            foreach ($page->items as $mention) {
                $host = strtolower((string) parse_url($mention->link, PHP_URL_HOST));
                if (! $this->publicDomain($host) || ! $this->matchesDomains($host, $configuration['domains'])) {
                    continue;
                }
                $date = AdapterSupport::date($mention->publishedAt);
                // Unknown publication dates remain null; the collector records received_at independently.
                if ($date !== null && ! AdapterSupport::inWindow($date, $from, $until)) {
                    continue;
                }
                if ($date === null) {
                    $warnings[] = 'publication_date_unavailable';
                }
                $items[] = ['external_id' => hash('sha256', $this->fingerprints->linkKey($mention->link)),
                    'url' => $mention->link, 'title' => $mention->title, 'text' => $mention->snippet, 'author' => $host,
                    'published_at' => $date?->toIso8601String(), 'metrics' => []];
            }
            $fingerprint = hash('sha256', implode('|', array_map(fn ($item) => $this->fingerprints->linkKey($item->link), $page->items)));
            $repeated = $page->items !== [] && ($state['fingerprint'] ?? null) === $fingerprint;
            if ($repeated) {
                $warnings[] = 'news_pagination_repeated';
            }
            $complete = $page->complete || $repeated;
            $next = $complete ? null : json_encode(['page' => $state['page'] + 1, 'fingerprint' => $fingerprint], JSON_THROW_ON_ERROR);

            return new CollectionPage($items, $next, $complete, array_values(array_unique($warnings)));
        });
    }

    private function publicDomain(string $domain): bool
    {
        return mb_strlen($domain) <= 253 && filter_var($domain, FILTER_VALIDATE_IP) === false
            && preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/D', $domain) === 1
            && ! preg_match('/\.(?:local|localhost|internal|test|invalid)$/D', $domain);
    }

    private function matchesDomains(string $host, array $domains): bool
    {
        if ($domains === []) {
            return true;
        }
        foreach ($domains as $domain) {
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return true;
            }
        }

        return false;
    }
}
