<?php

namespace App\Modules\YouTube\Analytics\Reports;

use App\Exceptions\Public\ExternalServiceRequestException;
use App\Exceptions\Public\ExternalServiceUnavailableException;
use App\Exceptions\Public\PublicResourceNotFoundException;
use App\Modules\YouTube\Analytics\YouTubeAnalyticsReportBuilder;
use App\Modules\YouTube\Core\Contracts\YouTubeGatewayInterface;
use App\Modules\YouTube\Core\Contracts\YouTubeUploadsGatewayInterface;
use App\Modules\YouTube\Presenters\YouTubeChannelPresenter;
use App\Modules\YouTube\Presenters\YouTubeVideoPresenter;
use App\Modules\YouTube\Support\YouTubeChannelResolver;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Throwable;

class ScheduledYouTubeAnalytics
{
    private const API_MAX_RESULTS = 50;

    public function __construct(
        private readonly YouTubeGatewayInterface $gateway,
        private readonly YouTubeUploadsGatewayInterface $uploadsGateway,
        private readonly YouTubeChannelResolver $channelResolver,
        private readonly YouTubeVideoPresenter $videoPresenter,
        private readonly YouTubeChannelPresenter $channelPresenter,
        private readonly YouTubeAnalyticsReportBuilder $reportBuilder,
    ) {}

    /**
     * Statistics are current lifetime totals of public videos published within the
     * requested interval. They do not measure changes in views during that interval.
     *
     * @return array<string, mixed>
     */
    public function build(string $channelInput, CarbonImmutable $from, CarbonImmutable $to, string $timezone): array
    {
        if ($from->greaterThan($to)) {
            throw new InvalidArgumentException('The report start must precede its end.');
        }

        $channelInput = PublicYouTubeChannel::normalize($channelInput) ?? throw $this->channelNotFound();
        $localFrom = $from->setTimezone($timezone);
        $localTo = $to->setTimezone($timezone);
        $channelId = $this->channelResolver->resolve($channelInput);
        $channel = $this->channel($channelId);
        $uploadsId = $channel['uploadsPlaylistId'];
        $videoIds = $this->publishedVideoIds($uploadsId, $from->utc(), $to->utc());
        $videos = $this->videos($videoIds, $channelId, $from->utc(), $to->utc());
        $missingMetrics = $this->missingMetricCounts($videos);

        return [
            'mode' => 'channel',
            'channelId' => $channelId,
            'channel' => $channel,
            'video' => null,
            'totals' => $this->totals($videos, $missingMetrics),
            'distribution' => $this->distribution($videos, $timezone),
            'leaders' => $this->leaders($videos),
            'insights' => array_values(array_filter($this->reportBuilder->insights($videos),
                fn (array $insight): bool => $insight['key'] !== 'engagement' || array_sum($missingMetrics) === 0)),
            'topTags' => $this->reportBuilder->topTags($videos),
            'topVideos' => $this->reportBuilder->topBy($videos, 'views', self::API_MAX_RESULTS),
            'range' => [
                'channelInput' => $channelInput,
                'dateFrom' => $localFrom->format('Y-m-d\TH:i:s.uP'),
                'dateTo' => $localTo->format('Y-m-d\TH:i:s.uP'),
                'timezone' => $timezone,
                'periodDays' => max(1, (int) $localFrom->startOfDay()->diffInDays($localTo->startOfDay()) + 1),
            ],
            'methodology' => [
                'source' => 'youtube_data_api',
                'metricsBasis' => 'lifetime_statistics_of_public_videos_published_in_range',
                'description' => 'Current lifetime views, likes and comments of public videos published in the selected interval. These are not statistics gained during the interval or private YouTube Analytics API data.',
                'complete' => true,
                'statisticsComplete' => array_sum($missingMetrics) === 0,
                'missingMetricCounts' => $missingMetrics,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function channel(string $channelId): array
    {
        if ($channelId === '') {
            throw $this->channelNotFound();
        }

        $items = $this->items($this->gateway->channels(['id' => $channelId, 'maxResults' => 1]));
        if ($items === []) {
            throw $this->channelNotFound();
        }

        $item = $items[0];
        if (($item['id'] ?? null) !== $channelId || ! is_string(Arr::get($item, 'snippet.title'))) {
            throw $this->invalidResponse();
        }
        $privacyStatus = Arr::get($item, 'status.privacyStatus');
        if ($privacyStatus !== null && $privacyStatus !== 'public') {
            throw $this->channelNotFound();
        }

        $channel = $this->channelPresenter->present($item);
        if (! is_string($channel['uploadsPlaylistId']) || preg_match('/^[A-Za-z0-9_-]+$/D', $channel['uploadsPlaylistId']) !== 1) {
            throw $this->invalidResponse();
        }

        return $channel;
    }

    /**
     * Scan every uploads page: playlist insertion order is not a guarantee of
     * video publication order. Search by channel alone is limited to 500 videos.
     *
     * @return list<string>
     */
    private function publishedVideoIds(string $uploadsId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $ids = [];
        $seenTokens = [];
        $pageToken = null;
        $maxPages = max(1, (int) config('youtube_analytics_reports.fetch_max_pages', 20));

        for ($page = 0; $page < $maxPages; $page++) {
            $payload = $this->uploadsGateway->playlistItems([
                'playlistId' => $uploadsId,
                'maxResults' => self::API_MAX_RESULTS,
                ...($pageToken !== null ? ['pageToken' => $pageToken] : []),
            ]);

            foreach ($this->items($payload) as $item) {
                $privacyStatus = Arr::get($item, 'status.privacyStatus');
                if (in_array($privacyStatus, ['private', 'unlisted'], true)) {
                    continue;
                }
                if ($privacyStatus !== null && $privacyStatus !== 'public') {
                    throw $this->invalidResponse();
                }

                $id = Arr::get($item, 'contentDetails.videoId');
                if (! is_string($id) || preg_match('/^[A-Za-z0-9_-]{11}$/D', $id) !== 1) {
                    throw $this->invalidResponse();
                }
                $publishedAt = $this->publicationDate(Arr::get($item, 'contentDetails.videoPublishedAt'));
                if ($publishedAt->betweenIncluded($from, $to)) {
                    $ids[$id] = $id;
                }
            }

            if (! array_key_exists('nextPageToken', $payload)) {
                return array_values($ids);
            }
            $nextToken = $payload['nextPageToken'];
            if (! is_string($nextToken) || $nextToken === '' || mb_strlen($nextToken) > 2048
                || preg_match('/\s|\p{C}/u', $nextToken) !== 0 || isset($seenTokens[$nextToken])) {
                throw new ExternalServiceRequestException('youtube_analytics_reports.errors.pagination_stalled', 422, 'youtube_analytics_pagination_stalled');
            }
            $seenTokens[$nextToken] = true;
            $pageToken = $nextToken;
        }

        throw new ExternalServiceRequestException('youtube_analytics_reports.errors.collection_limit', 422, 'youtube_analytics_collection_limit');
    }

    /**
     * @param  list<string>  $ids
     * @return list<array<string, mixed>>
     */
    private function videos(array $ids, string $channelId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $videos = [];
        foreach (array_chunk($ids, self::API_MAX_RESULTS) as $chunk) {
            $payload = $this->gateway->videos(['id' => implode(',', $chunk), 'maxResults' => self::API_MAX_RESULTS]);
            $seen = [];
            foreach ($this->items($payload) as $item) {
                $id = $item['id'] ?? null;
                if (! is_string($id) || ! in_array($id, $chunk, true) || isset($seen[$id])
                    || Arr::get($item, 'snippet.channelId') !== $channelId) {
                    throw $this->invalidResponse();
                }
                $seen[$id] = true;
                $privacyStatus = Arr::get($item, 'status.privacyStatus');
                if (in_array($privacyStatus, ['private', 'unlisted'], true)) {
                    continue;
                }
                if ($privacyStatus !== 'public') {
                    throw $this->invalidResponse();
                }
                $publishedAt = $this->publicationDate(Arr::get($item, 'snippet.publishedAt'));
                if (! $publishedAt->betweenIncluded($from, $to)) {
                    continue;
                }
                $statistics = $item['statistics'] ?? null;
                if (! is_array($statistics)) {
                    throw $this->invalidResponse();
                }
                foreach (['viewCount', 'likeCount', 'commentCount'] as $field) {
                    if ($field !== 'viewCount' && ! array_key_exists($field, $statistics)) {
                        continue;
                    }
                    $count = $statistics[$field] ?? null;
                    if ((! is_string($count) && ! is_int($count)) || preg_match('/^\d+$/D', (string) $count) !== 1
                        || filter_var($count, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) {
                        throw $this->invalidResponse();
                    }
                }
                if (! is_string(Arr::get($item, 'snippet.title')) || ! is_array($item['contentDetails'] ?? null)) {
                    throw $this->invalidResponse();
                }
                $video = $this->videoPresenter->present($item);
                $video['likes'] = array_key_exists('likeCount', $statistics) ? $video['likes'] : null;
                $video['comments'] = array_key_exists('commentCount', $statistics) ? $video['comments'] : null;
                if ($video['likes'] === null || $video['comments'] === null) {
                    $video['engagementRate'] = null;
                }
                $videos[] = $video;
            }
            if (count($seen) !== count($chunk)) {
                throw $this->invalidResponse();
            }
        }

        return $videos;
    }

    /**
     * @param  list<array<string, mixed>>  $videos
     * @return array{likes: int, comments: int}
     */
    private function missingMetricCounts(array $videos): array
    {
        return [
            'likes' => count(array_filter($videos, fn (array $video): bool => $video['likes'] === null)),
            'comments' => count(array_filter($videos, fn (array $video): bool => $video['comments'] === null)),
        ];
    }

    /**
     * Retain sums of known counters without representing missing data as zero.
     *
     * @param  list<array<string, mixed>>  $videos
     * @param  array{likes: int, comments: int}  $missingMetrics
     * @return array<string, int|float|null>
     */
    private function totals(array $videos, array $missingMetrics): array
    {
        $totals = $this->reportBuilder->totals($videos);
        foreach (['likes' => 'likeRate', 'comments' => 'commentRate'] as $metric => $rate) {
            $totals[$metric.'Known'] = $totals[$metric];
            if ($missingMetrics[$metric] > 0) {
                $totals[$metric] = null;
                $totals['avg'.ucfirst($metric)] = null;
                $totals[$rate] = null;
            }
        }
        if (array_sum($missingMetrics) > 0) {
            $totals['engagementRate'] = null;
        }

        return $totals;
    }

    /**
     * @param  list<array<string, mixed>>  $videos
     * @return array<string, mixed>
     */
    private function distribution(array $videos, string $timezone): array
    {
        $distribution = $this->reportBuilder->distribution($videos, $timezone);
        $missingByDay = [];
        foreach ($videos as $video) {
            $day = CarbonImmutable::parse($video['publishedAt'])->setTimezone($timezone)->toDateString();
            foreach (['likes', 'comments'] as $metric) {
                if ($video[$metric] === null) {
                    $missingByDay[$day][$metric] = ($missingByDay[$day][$metric] ?? 0) + 1;
                }
            }
        }
        foreach ($distribution['timeline'] as &$row) {
            foreach (['likes', 'comments'] as $metric) {
                $row[$metric.'Known'] = $row[$metric];
                if (($missingByDay[$row['key']][$metric] ?? 0) > 0) {
                    $row[$metric] = null;
                }
            }
        }
        unset($row);

        return $distribution;
    }

    /**
     * Rankings cannot compare a known number to an unavailable counter.
     *
     * @param  list<array<string, mixed>>  $videos
     * @return array<string, list<array<string, mixed>>>
     */
    private function leaders(array $videos): array
    {
        $leaders = ['byViews' => $this->reportBuilder->topBy($videos, 'views')];
        foreach (['byLikes' => 'likes', 'byComments' => 'comments', 'byEngagement' => 'engagementRate'] as $key => $metric) {
            $known = array_values(array_filter($videos, fn (array $video): bool => $video[$metric] !== null));
            $leaders[$key] = $this->reportBuilder->topBy($known, $metric);
        }

        return $leaders;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private function items(array $payload): array
    {
        $items = $payload['items'] ?? null;
        if (isset($payload['error']) || ! is_array($items) || ! array_is_list($items)) {
            throw $this->invalidResponse();
        }
        foreach ($items as $item) {
            if (! is_array($item)) {
                throw $this->invalidResponse();
            }
        }

        return $items;
    }

    private function publicationDate(mixed $value): CarbonImmutable
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})$/D', $value) !== 1) {
            throw $this->invalidResponse();
        }
        try {
            $date = new DateTimeImmutable($value);
            $errors = DateTimeImmutable::getLastErrors();
            if ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
                throw $this->invalidResponse();
            }

            return CarbonImmutable::instance($date)->utc();
        } catch (Throwable $exception) {
            if ($exception instanceof ExternalServiceUnavailableException) {
                throw $exception;
            }
            throw $this->invalidResponse($exception);
        }
    }

    private function invalidResponse(?Throwable $previous = null): ExternalServiceUnavailableException
    {
        return new ExternalServiceUnavailableException('errors.api.youtube.request_failed', 'youtube_analytics_invalid_response', $previous);
    }

    private function channelNotFound(): PublicResourceNotFoundException
    {
        return new PublicResourceNotFoundException('errors.api.youtube.channel_not_found', 'youtube_channel_not_found');
    }
}
