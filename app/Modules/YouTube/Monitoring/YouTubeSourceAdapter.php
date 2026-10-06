<?php

namespace App\Modules\YouTube\Monitoring;

use App\Models\MonitoringSource;
use App\Modules\YouTube\Core\Contracts\YouTubeGatewayInterface;
use App\Modules\YouTube\Support\YouTubeChannelResolver;
use App\Services\Monitoring\AdapterSupport;
use App\Services\Monitoring\CollectionPage;
use App\Services\Monitoring\Contracts\SourceAdapter;
use App\Services\Monitoring\SourceUnavailable;
use Carbon\CarbonImmutable;

final readonly class YouTubeSourceAdapter implements SourceAdapter
{
    public function __construct(private YouTubeGatewayInterface $gateway, private YouTubeChannelResolver $resolver) {}

    public function resolve(string $input): array
    {
        $input = trim($input);
        if (str_contains($input, '://')) {
            $parts = parse_url($input);
            if (! is_array($parts) || ! in_array(strtolower($parts['host'] ?? ''), ['youtube.com', 'www.youtube.com'], true)
                || isset($parts['user']) || isset($parts['pass'])
                || preg_match('~^/(?:channel/([^/]+)|(@[^/]+)|user/([^/]+))/?$~D', rawurldecode($parts['path'] ?? ''), $matches) !== 1) {
                throw new SourceUnavailable('invalid_source');
            }
            $input = $matches[1] ?: ($matches[2] ?: ($matches[3] ?? ''));
        }
        if ($input === '' || preg_match('/^@?[\p{L}\p{N}_.-]{1,100}$/uD', $input) !== 1) {
            throw new SourceUnavailable('invalid_source');
        }

        return AdapterSupport::request('youtube', function () use ($input): array {
            $id = $this->resolver->resolve($input);
            $channel = $this->gateway->channels(['id' => $id, 'part' => 'snippet,contentDetails', 'maxResults' => 1])['items'][0] ?? null;
            $uploads = data_get($channel, 'contentDetails.relatedPlaylists.uploads');
            if (! is_array($channel) || ($channel['id'] ?? '') !== $id || ! is_string($uploads) || $uploads === '') {
                throw new SourceUnavailable('youtube_channel_unavailable');
            }

            return ['identity' => $id, 'title' => AdapterSupport::text(data_get($channel, 'snippet.title', $id), 255),
                'configuration' => ['channel_id' => $id, 'uploads_playlist_id' => $uploads]];
        });
    }

    public function fetch(MonitoringSource $source, CarbonImmutable $from, CarbonImmutable $until, ?string $cursor): CollectionPage
    {
        if (($source->configuration['channel_id'] ?? '') !== $source->identity
            || empty($source->configuration['uploads_playlist_id'])) {
            throw new SourceUnavailable('invalid_source');
        }

        return AdapterSupport::request('youtube', function () use ($source, $from, $until, $cursor): CollectionPage {
            $payload = $this->gateway->playlistItems(array_filter([
                'playlistId' => $source->configuration['uploads_playlist_id'], 'maxResults' => AdapterSupport::pageSize(50),
                'part' => 'snippet,contentDetails', 'pageToken' => $cursor,
            ], fn ($value) => $value !== null));
            if (! is_array($payload['items'] ?? null)) {
                throw new SourceUnavailable('youtube_invalid_response');
            }
            $ids = [];
            $reachedStart = false;
            $warnings = [];
            foreach ($payload['items'] as $upload) {
                $date = AdapterSupport::date(data_get($upload, 'contentDetails.videoPublishedAt'));
                $id = data_get($upload, 'contentDetails.videoId', data_get($upload, 'snippet.resourceId.videoId'));
                if ($date?->lt($from)) {
                    $reachedStart = true;
                }
                if ($date === null) {
                    $warnings[] = 'video_date_unavailable';
                }
                if (is_string($id) && preg_match('/^[A-Za-z0-9_-]{1,64}$/D', $id) === 1
                    && ($date === null || AdapterSupport::inWindow($date, $from, $until))) {
                    $ids[] = $id;
                }
            }
            $items = [];
            if ($ids !== []) {
                $videos = $this->gateway->videos(['id' => implode(',', array_unique($ids)), 'part' => 'snippet,statistics,status']);
                if (! is_array($videos['items'] ?? null)) {
                    throw new SourceUnavailable('youtube_invalid_response');
                }
                $found = [];
                foreach ($videos['items'] as $video) {
                    $id = (string) ($video['id'] ?? '');
                    if (! in_array($id, $ids, true) || data_get($video, 'snippet.channelId') !== $source->identity) {
                        throw new SourceUnavailable('youtube_identity_changed');
                    }
                    $found[] = $id;
                    $date = AdapterSupport::date(data_get($video, 'snippet.publishedAt'));
                    if ($date === null || data_get($video, 'status.privacyStatus', 'public') !== 'public') {
                        $warnings[] = 'video_unavailable';

                        continue;
                    }
                    if (! AdapterSupport::inWindow($date, $from, $until)) {
                        continue;
                    }
                    $items[] = ['external_id' => $id, 'url' => 'https://www.youtube.com/watch?v='.$id,
                        'title' => AdapterSupport::text(data_get($video, 'snippet.title'), 1000),
                        'text' => AdapterSupport::text(data_get($video, 'snippet.description')),
                        'author' => AdapterSupport::text(data_get($video, 'snippet.channelTitle'), 255),
                        'published_at' => $date->toIso8601String(),
                        'metrics' => ['views' => max(0, (int) data_get($video, 'statistics.viewCount', 0)),
                            'likes' => max(0, (int) data_get($video, 'statistics.likeCount', 0)),
                            'comments' => max(0, (int) data_get($video, 'statistics.commentCount', 0))]];
                }
                if (array_diff($ids, $found) !== []) {
                    $warnings[] = 'private_or_deleted_videos';
                }
            }
            $next = $reachedStart ? null : AdapterSupport::nextCursor($payload['nextPageToken'] ?? null, $cursor);

            return new CollectionPage($items, $next, $next === null, array_values(array_unique($warnings)));
        });
    }
}
