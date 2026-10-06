<?php

namespace App\Modules\Bluesky\Monitoring;

use App\Models\MonitoringSource;
use App\Modules\Bluesky\Core\Contracts\BlueskyGatewayInterface;
use App\Modules\Bluesky\Support\BlueskyActorResolver;
use App\Services\Monitoring\AdapterSupport;
use App\Services\Monitoring\CollectionPage;
use App\Services\Monitoring\Contracts\SourceAdapter;
use App\Services\Monitoring\SourceUnavailable;
use Carbon\CarbonImmutable;

final readonly class BlueskySourceAdapter implements SourceAdapter
{
    public function __construct(private BlueskyGatewayInterface $gateway, private BlueskyActorResolver $resolver) {}

    public function resolve(string $input): array
    {
        $input = trim($input);
        if (preg_match('~^https://bsky\.app/profile/([^/?#]+)/?$~D', $input, $matches) === 1) {
            $input = $matches[1];
        }
        $input = ltrim($input, '@');
        if (preg_match('/^(?:did:(?:plc|web):[A-Za-z0-9.:%_-]+|[A-Za-z0-9.-]+\.[A-Za-z]{2,})$/D', $input) !== 1) {
            throw new SourceUnavailable('invalid_source');
        }

        return AdapterSupport::request('bluesky', function () use ($input): array {
            // Never use fuzzy search fallback: it can silently substitute another account.
            $profile = $this->resolver->resolve($input, false);
            $did = $profile['did'] ?? '';
            if (! is_string($did) || ! str_starts_with($did, 'did:')
                || (str_starts_with($input, 'did:') ? $input !== $did : strcasecmp($profile['handle'] ?? '', $input) !== 0)) {
                throw new SourceUnavailable('bluesky_account_unavailable');
            }

            return ['identity' => $did, 'title' => AdapterSupport::text($profile['displayName'] ?: ($profile['handle'] ?? $did), 255),
                'configuration' => ['did' => $did, 'handle' => $profile['handle'] ?? $input, 'filter' => 'posts_with_replies']];
        });
    }

    public function fetch(MonitoringSource $source, CarbonImmutable $from, CarbonImmutable $until, ?string $cursor): CollectionPage
    {
        if (($source->configuration['did'] ?? '') !== $source->identity || ! str_starts_with($source->identity, 'did:')) {
            throw new SourceUnavailable('invalid_source');
        }

        return AdapterSupport::request('bluesky', function () use ($source, $from, $until, $cursor): CollectionPage {
            $payload = $this->gateway->getAuthorFeed($source->identity, AdapterSupport::pageSize(), $cursor, 'posts_with_replies');
            if (! is_array($payload['feed'] ?? null)) {
                throw new SourceUnavailable('bluesky_invalid_response');
            }
            $items = [];
            $warnings = [];
            $reachedStart = false;
            foreach ($payload['feed'] as $entry) {
                // Author feeds may contain reposts; their original creation date is not this author's publication.
                if (isset($entry['reason'])) {
                    continue;
                }
                $post = $entry['post'] ?? [];
                $date = AdapterSupport::date(data_get($post, 'record.createdAt'));
                if ($date === null) {
                    $warnings[] = 'publication_date_unavailable';

                    continue;
                }
                if ($date->lt($from) && ! ($entry['isPinned'] ?? false)) {
                    $reachedStart = true;
                }
                if (! AdapterSupport::inWindow($date, $from, $until)) {
                    continue;
                }
                $uri = (string) ($post['uri'] ?? '');
                if (data_get($post, 'author.did') !== $source->identity
                    || ! str_starts_with($uri, 'at://'.$source->identity.'/app.bsky.feed.post/')) {
                    throw new SourceUnavailable('bluesky_identity_changed');
                }
                $id = basename($uri);
                $items[] = ['external_id' => $uri, 'url' => 'https://bsky.app/profile/'.rawurlencode($source->identity).'/post/'.rawurlencode($id),
                    'title' => '', 'text' => AdapterSupport::text(data_get($post, 'record.text')),
                    'author' => AdapterSupport::text(data_get($post, 'author.handle', $source->identity), 255),
                    'published_at' => $date->toIso8601String(),
                    'metrics' => ['likes' => max(0, (int) ($post['likeCount'] ?? 0)),
                        'reposts' => max(0, (int) ($post['repostCount'] ?? 0)), 'replies' => max(0, (int) ($post['replyCount'] ?? 0))]];
            }
            $next = $reachedStart ? null : AdapterSupport::nextCursor($payload['cursor'] ?? null, $cursor);

            return new CollectionPage($items, $next, $next === null, array_values(array_unique($warnings)));
        });
    }
}
