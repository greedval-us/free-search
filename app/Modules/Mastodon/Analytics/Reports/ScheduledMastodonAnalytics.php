<?php

namespace App\Modules\Mastodon\Analytics\Reports;

use App\Exceptions\Public\ExternalServiceRequestException;
use App\Exceptions\Public\PublicResourceNotFoundException;
use App\Modules\Mastodon\Analytics\MastodonAnalyticsReportBuilder;
use App\Modules\Mastodon\Core\Contracts\MastodonGatewayInterface;
use App\Modules\Mastodon\Presenters\MastodonAccountPresenter;
use App\Modules\Mastodon\Presenters\MastodonStatusPresenter;
use App\Modules\Mastodon\Support\MastodonApiConfig;
use App\Support\PublicMastodonAccount;
use Carbon\CarbonImmutable;
use Throwable;

class ScheduledMastodonAnalytics
{
    public function __construct(
        private readonly MastodonGatewayInterface $gateway,
        private readonly MastodonStatusPresenter $statusPresenter,
        private readonly MastodonAccountPresenter $accountPresenter,
        private readonly MastodonAnalyticsReportBuilder $reportBuilder,
        private readonly AnalyticsReportConfig $config,
        private readonly MastodonApiConfig $apiConfig,
    ) {}

    public function build(string $accountInput, CarbonImmutable $from, CarbonImmutable $to, string $timezone): array
    {
        $account = PublicMastodonAccount::normalize($accountInput);
        if ($account === null || $from->gt($to)) {
            throw new AnalyticsReportException('invalid_accounts');
        }
        $rawProfile = $this->resolve($account);
        $profile = $this->accountPresenter->present($rawProfile);
        $maxId = null;
        $seenCursors = [];
        $seenIds = [];
        $statuses = [];
        $pagesLoaded = 0;
        $maxPages = $this->config->integer('fetch_max_pages');

        do {
            $payload = $this->gateway->accountStatuses($profile['id'], 40, $maxId);
            $pagesLoaded++;
            if (! isset($payload['items']) || ! is_array($payload['items']) || ! array_is_list($payload['items'])
                || ! isset($payload['pagination']) || ! is_array($payload['pagination'])
                || ! array_key_exists('nextMaxId', $payload['pagination'])) {
                $this->invalidResponse();
            }
            $next = $payload['pagination']['nextMaxId'];
            if ($next !== null && (! is_string($next) || preg_match('/^[0-9]+$/D', $next) !== 1)) {
                $this->invalidResponse();
            }
            $newIds = 0;
            foreach ($payload['items'] as $item) {
                $created = $this->validateStatus($item, $profile['id']);
                if (isset($seenIds[$item['id']])) {
                    continue;
                }
                $seenIds[$item['id']] = true;
                $newIds++;
                // Boost wrappers contain somebody else's post; their publication date is not this account's original post date.
                if (($item['reblog'] ?? null) !== null || ! in_array($item['visibility'], ['public', 'unlisted'], true)
                    || $created->lt($from) || $created->gt($to)) {
                    continue;
                }
                $statuses[] = $this->statusPresenter->present($item);
            }
            if ($next !== null) {
                if ($newIds === 0 || isset($seenCursors[$next])) {
                    $this->collectionError('pagination_stalled');
                }
                if ($pagesLoaded >= $maxPages) {
                    $this->collectionError('collection_limit');
                }
                $seenCursors[$next] = true;
            }
            $maxId = $next;
        } while ($maxId !== null);

        $report = $this->reportBuilder->build('account', $account, $profile, $statuses, $maxPages, $pagesLoaded, $timezone)->toArray();

        return [...$report,
            'range' => ['accountInput' => $account, 'dateFrom' => $from->utc()->toIso8601String(),
                'dateTo' => $to->utc()->format('Y-m-d\TH:i:s.uP'), 'timezone' => $timezone,
                'periodDays' => (int) $from->setTimezone($timezone)->startOfDay()->diffInDays($to->setTimezone($timezone)->startOfDay()) + 1],
            'methodology' => ['source' => 'Mastodon API', 'metricsBasis' => 'current_totals_for_posts_published_in_period',
                'collectionScope' => 'statuses_available_on_configured_instance', 'instance' => $this->apiConfig->baseUrl(),
                'complete' => true, 'excludesBoosts' => true, 'visibility' => ['public', 'unlisted']],
        ];
    }

    private function resolve(string $account): array
    {
        try {
            $profile = $this->gateway->lookupAccount($account);
        } catch (ExternalServiceRequestException $exception) {
            if ($exception->status() !== 404) {
                throw $exception;
            }
            // Resolution happens on the configured server, never by following the submitted profile URL.
            $payload = $this->gateway->search(['q' => $account, 'type' => 'accounts', 'resolve' => 'true', 'limit' => 10]);
            if (! isset($payload['accounts']) || ! is_array($payload['accounts']) || ! array_is_list($payload['accounts'])) {
                $this->invalidResponse();
            }
            $profile = collect($payload['accounts'])->first(fn ($item): bool => is_array($item) && $this->matchesAccount($item, $account));
            if (! is_array($profile)) {
                throw new PublicResourceNotFoundException('errors.api.mastodon.account_not_found', 'mastodon_account_not_found');
            }
        }
        if (! is_string($profile['id'] ?? null) || preg_match('/^[0-9]+$/D', $profile['id']) !== 1
            || ! $this->matchesAccount($profile, $account)) {
            $this->invalidResponse();
        }
        foreach (['followers_count', 'following_count', 'statuses_count'] as $metric) {
            if (! is_int($profile[$metric] ?? null) || $profile[$metric] < 0) {
                $this->invalidResponse();
            }
        }

        return $profile;
    }

    private function matchesAccount(array $profile, string $account): bool
    {
        return PublicMastodonAccount::normalize($profile['acct'] ?? null) === $account
            || PublicMastodonAccount::normalize($profile['url'] ?? null) === $account;
    }

    private function validateStatus(mixed $item, string $accountId): CarbonImmutable
    {
        if (! is_array($item) || ! is_string($item['id'] ?? null) || preg_match('/^[0-9]+$/D', $item['id']) !== 1
            || ! is_string($item['created_at'] ?? null)
            || preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/D', $item['created_at']) !== 1
            || ! is_array($item['account'] ?? null) || ($item['account']['id'] ?? null) !== $accountId
            || ! is_string($item['content'] ?? null)
            || ! in_array($item['visibility'] ?? null, ['public', 'unlisted', 'private', 'direct'], true)
            || (($item['reblog'] ?? null) !== null && ! is_array($item['reblog']))) {
            $this->invalidResponse();
        }
        foreach (['replies_count', 'reblogs_count', 'favourites_count'] as $metric) {
            if (! is_int($item[$metric] ?? null) || $item[$metric] < 0) {
                $this->invalidResponse();
            }
        }
        foreach (['media_attachments', 'mentions', 'tags'] as $field) {
            if (! is_array($item[$field] ?? null) || ! array_is_list($item[$field])
                || collect($item[$field])->contains(fn ($entry): bool => ! is_array($entry))) {
                $this->invalidResponse();
            }
        }
        try {
            $created = CarbonImmutable::parse($item['created_at']);
            if ($created->format('Y-m-d\TH:i:s') !== substr($item['created_at'], 0, 19)) {
                $this->invalidResponse();
            }

            return $created->utc();
        } catch (Throwable) {
            $this->invalidResponse();
        }
    }

    private function invalidResponse(): never
    {
        throw new ExternalServiceRequestException('errors.api.mastodon.request_failed', 502, 'mastodon_analytics_invalid_response');
    }

    private function collectionError(string $reason): never
    {
        throw new ExternalServiceRequestException('errors.api.mastodon.request_failed', 422, 'mastodon_analytics_'.$reason);
    }
}
