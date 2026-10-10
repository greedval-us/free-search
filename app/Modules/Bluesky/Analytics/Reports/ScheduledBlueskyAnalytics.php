<?php

namespace App\Modules\Bluesky\Analytics\Reports;

use App\Exceptions\Public\ExternalServiceRequestException;
use App\Exceptions\Public\PublicResourceNotFoundException;
use App\Modules\Bluesky\Analytics\BlueskyAnalyticsReportBuilder;
use App\Modules\Bluesky\Analytics\Reports\Contracts\ScheduledBlueskyGatewayInterface;
use App\Modules\Bluesky\Presenters\BlueskyActorPresenter;
use App\Modules\Bluesky\Presenters\BlueskyPostPresenter;
use App\Support\PublicBlueskyAccount;
use Carbon\CarbonImmutable;
use Throwable;

class ScheduledBlueskyAnalytics
{
    private const METRICS = ['replyCount' => 'totalReplies', 'repostCount' => 'totalReposts',
        'likeCount' => 'totalLikes', 'quoteCount' => 'totalQuotes'];

    public function __construct(
        private readonly ScheduledBlueskyGatewayInterface $gateway,
        private readonly BlueskyPostPresenter $postPresenter,
        private readonly BlueskyActorPresenter $actorPresenter,
        private readonly BlueskyAnalyticsReportBuilder $builder,
        private readonly AnalyticsReportConfig $config,
    ) {}

    public function build(string $accountInput, CarbonImmutable $from, CarbonImmutable $to, string $timezone): array
    {
        $actor = PublicBlueskyAccount::normalize($accountInput);
        if ($actor === null || $from->gt($to)) {
            throw new AnalyticsReportException('invalid_accounts');
        }
        // Stored SQL timestamps retain whole seconds; publication timestamps can contain fractions.
        $to = $to->endOfSecond();
        $profile = $this->profile($actor);
        $did = $profile['did'];
        $cursor = null;
        $seenCursors = [];
        $posts = [];
        $pagesLoaded = 0;
        $maxPages = $this->config->integer('fetch_max_pages');
        for ($page = 0; $page < $maxPages; $page++) {
            $payload = $this->gateway->getAuthorFeed($did, 100, $cursor);
            if (! isset($payload['feed']) || ! is_array($payload['feed']) || ! array_is_list($payload['feed'])) {
                $this->invalidResponse();
            }
            $pagesLoaded++;
            foreach ($payload['feed'] as $item) {
                if (! is_array($item) || ! is_array($item['post'] ?? null)) {
                    $this->invalidResponse();
                }
                $post = $item['post'];
                if (! is_string($post['author']['did'] ?? null)) {
                    $this->invalidResponse();
                }
                // Author feeds contain reposts by this account of other people's publications.
                if ($post['author']['did'] !== $did) {
                    continue;
                }
                if (! is_array($post['record'] ?? null) || ! is_string($post['record']['createdAt'] ?? null)
                    || ! is_string($post['record']['text'] ?? null) || ! is_string($post['uri'] ?? null)
                    || preg_match('~^at://'.preg_quote($did, '~').'/app\.bsky\.feed\.post/[^/\s]+$~D', $post['uri']) !== 1) {
                    $this->invalidResponse();
                }
                $createdAt = $this->timestamp($post['record']['createdAt']);
                if ($createdAt->lt($from) || $createdAt->gt($to)) {
                    continue;
                }
                $presented = $this->postPresenter->present($post);
                foreach (self::METRICS as $metric => $summary) {
                    $value = $post[$metric] ?? null;
                    if ($value !== null && (! is_int($value) || $value < 0)) {
                        $this->invalidResponse();
                    }
                    $presented[$metric] = $value;
                }
                $posts[$post['uri']] = $presented;
            }
            $next = $payload['cursor'] ?? null;
            if ($next !== null && ! is_string($next)) {
                $this->invalidResponse();
            }
            if ($next === null || $next === '') {
                break;
            }
            if (isset($seenCursors[$next])) {
                $this->failure('bluesky_analytics_pagination_stalled');
            }
            $seenCursors[$next] = true;
            $cursor = $next;
            if ($page === $maxPages - 1) {
                $this->failure('bluesky_analytics_collection_limit');
            }
        }
        $posts = array_values($posts);
        $result = $this->builder->build('account', $actor, $profile, $posts, $maxPages, $pagesLoaded, timezone: $timezone)->toArray();
        $postsByDay = collect($posts)->groupBy(fn (array $post): string => $this->timestamp($post['createdAt'])->setTimezone($timezone)->toDateString());
        $missing = [];
        $timelineMetrics = ['replyCount' => 'replies', 'repostCount' => 'reposts', 'likeCount' => 'likes', 'quoteCount' => 'quotes'];
        foreach (self::METRICS as $metric => $summary) {
            $missing[$metric] = count(array_filter($posts, static fn (array $post): bool => $post[$metric] === null));
            $result['summary'][$summary] = $missing[$metric] === 0 ? array_sum(array_column($posts, $metric)) : null;
            foreach ($result['timeline'] as &$point) {
                $dayPosts = $postsByDay->get($point['day'], collect());
                if ($dayPosts->contains(static fn (array $post): bool => $post[$metric] === null)) {
                    $point[$timelineMetrics[$metric]] = null;
                }
            }
            unset($point);
        }
        $ranked = collect($posts)->filter(static fn (array $post): bool => ! in_array(null, array_intersect_key($post, self::METRICS), true));
        $result['topPosts'] = $ranked->sortByDesc(static fn (array $post): int => array_sum(array_intersect_key($post, self::METRICS)))
            ->take(8)->values()->all();
        $localFrom = $from->setTimezone($timezone);
        $localTo = $to->setTimezone($timezone);
        $result['range'] = ['accountInput' => $actor, 'dateFrom' => $localFrom->format('Y-m-d\TH:i:s.uP'),
            'dateTo' => $localTo->format('Y-m-d\TH:i:s.uP'), 'timezone' => $timezone,
            'periodDays' => (int) $localFrom->startOfDay()->diffInDays($localTo->startOfDay()->addDay())];
        $result['methodology'] = ['source' => 'bluesky_public_author_feed', 'metricsBasis' => 'current_totals_for_posts_published_in_period',
            'description' => 'Current reaction totals for this account’s public posts and replies published in the selected period. Reposts of other accounts are excluded. This does not measure reaction growth during the period.',
            'complete' => true, 'statisticsComplete' => array_sum($missing) === 0, 'missingMetricCounts' => $missing];
        $result['generatedAt'] = CarbonImmutable::now()->toISOString();

        return $result;
    }

    private function profile(string $actor): array
    {
        $payload = $this->gateway->getProfiles([$actor]);
        if (! isset($payload['profiles']) || ! is_array($payload['profiles']) || ! array_is_list($payload['profiles'])) {
            $this->invalidResponse();
        }
        if ($payload['profiles'] === []) {
            throw new PublicResourceNotFoundException('errors.api.bluesky.account_not_found', 'bluesky_account_not_found');
        }
        $profile = $payload['profiles'][0];
        if (! is_array($profile) || ! is_string($profile['did'] ?? null)
            || ! str_starts_with($profile['did'], 'did:') || PublicBlueskyAccount::normalize($profile['did']) !== $profile['did']
            || (str_starts_with($actor, 'did:') ? $profile['did'] !== $actor
                : strtolower((string) ($profile['handle'] ?? '')) !== $actor)) {
            $this->invalidResponse();
        }

        return $this->actorPresenter->present($profile);
    }

    private function timestamp(string $value): CarbonImmutable
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/D', $value) !== 1) {
            $this->invalidResponse();
        }
        try {
            $timestamp = CarbonImmutable::parse($value);
            if ($timestamp->format('Y-m-d\TH:i:s') !== substr($value, 0, 19)) {
                $this->invalidResponse();
            }

            return $timestamp->utc();
        } catch (Throwable) {
            $this->invalidResponse();
        }
    }

    private function invalidResponse(): never
    {
        $this->failure('bluesky_invalid_response');
    }

    private function failure(string $code): never
    {
        throw new ExternalServiceRequestException('errors.api.bluesky.request_failed', 422, $code);
    }
}
