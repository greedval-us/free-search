<?php

namespace App\Modules\YouTube\Actions\Request;

use App\Modules\YouTube\Actions\AbstractYouTubeAction;
use App\Modules\YouTube\Analytics\YouTubeAnalyticsReportBuilder;
use App\Modules\YouTube\Core\Contracts\YouTubeGatewayInterface;
use App\Modules\YouTube\DTO\Request\YouTubeAnalyticsLookupDTO;
use App\Modules\YouTube\DTO\Result\YouTubeAnalyticsResultDTO;
use App\Modules\YouTube\Presenters\YouTubeChannelPresenter;
use App\Modules\YouTube\Presenters\YouTubeVideoPresenter;
use App\Modules\YouTube\Support\YouTubeChannelResolver;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;

class AnalyticsSummaryAction extends AbstractYouTubeAction
{
    public function __construct(
        YouTubeGatewayInterface $gateway,
        private readonly YouTubeAnalyticsReportBuilder $reportBuilder,
        private readonly YouTubeVideoPresenter $videoPresenter,
        private readonly YouTubeChannelPresenter $channelPresenter,
        private readonly YouTubeChannelResolver $channelResolver,
    ) {
        parent::__construct($gateway);
    }

    public function handle(YouTubeAnalyticsLookupDTO $query): YouTubeAnalyticsResultDTO
    {
        $params = $query->toArray();

        if ($params['mode'] === 'channel' || $params['channelId'] !== '') {
            return $this->channelSummary(
                channelId: $params['channelId'],
                periodDays: (int) ($params['periodDays'] ?? 7),
                dateFrom: (string) ($params['dateFrom'] ?? ''),
                dateTo: (string) ($params['dateTo'] ?? ''),
            );
        }

        return $this->videoSummary($params['videoId']);
    }

    private function videoSummary(string $videoId): YouTubeAnalyticsResultDTO
    {
        $video = $this->videosById([$videoId])[$videoId] ?? null;

        if ($video === null) {
            return $this->emptySummary('video');
        }

        $relatedVideos = $video['channelId'] !== ''
            ? array_values($this->latestChannelVideos($video['channelId'], 10))
            : [];
        $comparisonVideos = $relatedVideos === [] ? [$video] : $relatedVideos;

        return $this->summaryPayload(
            mode: 'video',
            videos: $comparisonVideos,
            video: $video,
            channel: $this->channelsById([$video['channelId']])[$video['channelId']] ?? null,
        );
    }

    private function channelSummary(string $channelId, int $periodDays, string $dateFrom, string $dateTo): YouTubeAnalyticsResultDTO
    {
        $resolvedChannelId = $this->channelResolver->resolve($channelId);
        $range = $this->resolveDateRange($periodDays, $dateFrom, $dateTo);
        $videos = array_values($this->latestChannelVideos($resolvedChannelId, 50, $range['from'], $range['to']));

        return $this->summaryPayload(
            mode: 'channel',
            videos: $videos,
            video: null,
            channel: $this->channelsById([$resolvedChannelId])[$resolvedChannelId] ?? null,
            channelId: $resolvedChannelId,
        );
    }

    private function summaryPayload(
        string $mode,
        array $videos,
        ?array $video,
        ?array $channel,
        ?string $channelId = null,
    ): YouTubeAnalyticsResultDTO {
        return new YouTubeAnalyticsResultDTO([
            'mode' => $mode,
            ...($channelId !== null ? ['channelId' => $channelId] : []),
            'video' => $video,
            'channel' => $channel,
            'totals' => $this->reportBuilder->totals($videos),
            'distribution' => $this->reportBuilder->distribution($videos),
            'leaders' => $this->reportBuilder->leaders($videos),
            'insights' => $this->reportBuilder->insights($videos, $video),
            'topTags' => $this->reportBuilder->topTags($videos),
            'topVideos' => $this->reportBuilder->topBy($videos, 'views', 50),
        ]);
    }

    private function emptySummary(string $mode): YouTubeAnalyticsResultDTO
    {
        return new YouTubeAnalyticsResultDTO([
            'mode' => $mode,
            'video' => null,
            'channel' => null,
            'totals' => $this->reportBuilder->totals([]),
            'distribution' => $this->reportBuilder->distribution([]),
            'leaders' => $this->reportBuilder->leaders([]),
            'insights' => [],
            'topTags' => [],
            'topVideos' => [],
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function latestChannelVideos(string $channelId, int $limit, ?CarbonInterface $publishedAfter = null, ?CarbonInterface $publishedBefore = null): array
    {
        if ($channelId === '') {
            return [];
        }

        $params = [
            'channelId' => $channelId,
            'type' => 'video',
            'order' => 'date',
            'maxResults' => $limit,
        ];

        if ($publishedAfter !== null) {
            $params['publishedAfter'] = $publishedAfter->copy()->utc()->toRfc3339String();
        }

        if ($publishedBefore !== null) {
            $params['publishedBefore'] = $publishedBefore->copy()->utc()->toRfc3339String();
        }

        $search = $this->gateway->search($params);

        $videoIds = collect($search['items'] ?? [])
            ->map(fn (array $item): ?string => Arr::get($item, 'id.videoId'))
            ->filter()
            ->values()
            ->all();

        return $this->videosById($videoIds);
    }

    /**
     * @param  array<int, string>  $videoIds
     * @return array<string, array<string, mixed>>
     */
    private function videosById(array $videoIds): array
    {
        $videoIds = array_values(array_filter(array_unique($videoIds)));

        if ($videoIds === []) {
            return [];
        }

        $payload = $this->gateway->videos([
            'id' => implode(',', $videoIds),
            'maxResults' => 50,
        ]);

        return collect($payload['items'] ?? [])
            ->mapWithKeys(fn (array $item): array => [$item['id'] => $this->videoPresenter->present($item)])
            ->all();
    }

    /**
     * @param  array<int, string>  $channelIds
     * @return array<string, array<string, mixed>>
     */
    private function channelsById(array $channelIds): array
    {
        $channelIds = array_values(array_filter(array_unique($channelIds)));

        if ($channelIds === []) {
            return [];
        }

        $payload = $this->gateway->channels([
            'id' => implode(',', $channelIds),
            'maxResults' => 50,
        ]);

        return collect($payload['items'] ?? [])
            ->mapWithKeys(fn (array $item): array => [$item['id'] => $this->channelPresenter->present($item)])
            ->all();
    }

    /**
     * @return array{from: Carbon, to: Carbon}
     */
    private function resolveDateRange(int $periodDays, string $dateFrom, string $dateTo): array
    {
        if ($dateFrom !== '' && $dateTo !== '') {
            return [
                'from' => Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay(),
                'to' => Carbon::createFromFormat('Y-m-d', $dateTo)->endOfDay(),
            ];
        }

        $days = in_array($periodDays, [1, 3, 7], true) ? $periodDays : 7;

        return [
            'from' => now()->subDays($days - 1)->startOfDay(),
            'to' => now()->endOfDay(),
        ];
    }
}
