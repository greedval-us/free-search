<?php

namespace App\Modules\Mastodon\Monitoring;

use App\Models\MonitoringSource;
use App\Modules\Mastodon\Actions\Request\LoadAccountStatusesAction;
use App\Modules\Mastodon\Core\Contracts\MastodonGatewayInterface;
use App\Modules\Mastodon\Support\MastodonApiConfig;
use App\Services\Monitoring\AdapterSupport;
use App\Services\Monitoring\CollectionPage;
use App\Services\Monitoring\Contracts\SourceAdapter;
use App\Services\Monitoring\SourceUnavailable;
use Carbon\CarbonImmutable;

final readonly class MastodonSourceAdapter implements SourceAdapter
{
    public function __construct(private MastodonGatewayInterface $gateway, private MastodonApiConfig $config,
        private LoadAccountStatusesAction $statuses) {}

    public function resolve(string $input): array
    {
        $input = trim($input);
        if (str_contains($input, '://')) {
            $parts = parse_url($input);
            if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass'])
                || isset($parts['port']) || preg_match('~^/@([A-Za-z0-9_.-]+)/?$~D', $parts['path'] ?? '', $matches) !== 1) {
                throw new SourceUnavailable('invalid_source');
            }
            $input = $matches[1].'@'.($parts['host'] ?? '');
        }
        $input = ltrim($input, '@');
        if (preg_match('/^[A-Za-z0-9_.-]+(?:@[A-Za-z0-9.-]+\.[A-Za-z]{2,})?$/D', $input) !== 1) {
            throw new SourceUnavailable('invalid_source');
        }

        return AdapterSupport::request('mastodon', function () use ($input): array {
            // Only the operator-configured instance is contacted. User URLs are account identifiers.
            $account = $this->gateway->lookupAccount($input);
            $id = (string) ($account['id'] ?? '');
            $uri = AdapterSupport::url($account['uri'] ?? $account['url'] ?? null);
            $acct = (string) ($account['acct'] ?? '');
            $local = (string) parse_url($this->config->baseUrl(), PHP_URL_HOST);
            $canonical = str_contains($acct, '@') ? $acct : $acct.'@'.$local;
            $requested = str_contains($input, '@') ? $input : $input.'@'.$local;
            if (! ctype_digit($id) || $uri === null || strcasecmp($canonical, $requested) !== 0) {
                throw new SourceUnavailable('mastodon_account_unavailable');
            }
            $instance = rtrim($this->config->baseUrl(), '/');

            return ['identity' => hash('sha256', $instance.'|'.$id.'|'.$uri),
                'title' => AdapterSupport::text(($account['display_name'] ?? '') ?: $canonical, 255),
                'configuration' => ['instance' => $instance, 'account_id' => $id, 'account_uri' => $uri, 'acct' => $canonical]];
        });
    }

    public function fetch(MonitoringSource $source, CarbonImmutable $from, CarbonImmutable $until, ?string $cursor): CollectionPage
    {
        $configuration = $source->configuration;
        if (($configuration['instance'] ?? '') !== rtrim($this->config->baseUrl(), '/')
            || ! ctype_digit((string) ($configuration['account_id'] ?? ''))
            || ($cursor !== null && ! ctype_digit($cursor))
            || $source->identity !== hash('sha256', $configuration['instance'].'|'.$configuration['account_id'].'|'.($configuration['account_uri'] ?? ''))) {
            throw new SourceUnavailable('mastodon_instance_changed');
        }

        return AdapterSupport::request('mastodon', function () use ($configuration, $from, $until, $cursor): CollectionPage {
            $payload = $this->statuses->handle($configuration['account_id'], AdapterSupport::pageSize(40), $cursor);
            $items = [];
            $warnings = ['federation_visibility_limited'];
            $reachedStart = false;
            foreach ($payload->statuses as $status) {
                $date = AdapterSupport::date($status['createdAt'] ?? null);
                if ($date === null) {
                    $warnings[] = 'publication_date_unavailable';

                    continue;
                }
                if ($date->lt($from)) {
                    $reachedStart = true;
                }
                if (! AdapterSupport::inWindow($date, $from, $until) || ($status['visibility'] ?? '') !== 'public'
                    || ($status['postType'] ?? '') === 'boost') {
                    continue;
                }
                if ((string) data_get($status, 'account.id') !== $configuration['account_id']) {
                    throw new SourceUnavailable('mastodon_identity_changed');
                }
                $uri = AdapterSupport::url($status['uri'] ?? null);
                $url = AdapterSupport::url($status['url'] ?? null);
                if ($uri === null || $url === null) {
                    $warnings[] = 'publication_url_unavailable';

                    continue;
                }
                $items[] = ['external_id' => $uri, 'url' => $url, 'title' => '',
                    'text' => AdapterSupport::text(trim(($status['spoilerText'] ?? '').' '.($status['content'] ?? ''))),
                    'author' => $configuration['acct'], 'published_at' => $date->toIso8601String(),
                    'metrics' => ['likes' => max(0, (int) ($status['favouritesCount'] ?? 0)),
                        'boosts' => max(0, (int) ($status['reblogsCount'] ?? 0)),
                        'replies' => max(0, (int) ($status['repliesCount'] ?? 0))]];
            }
            $next = $reachedStart ? null : AdapterSupport::nextCursor($payload->pagination['nextMaxId'] ?? null, $cursor);

            return new CollectionPage($items, $next, $next === null, array_values(array_unique($warnings)));
        });
    }
}
