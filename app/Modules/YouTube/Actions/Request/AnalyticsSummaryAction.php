<?php

namespace App\Modules\YouTube\Actions\Request;

use App\Modules\YouTube\Actions\AbstractYouTubeAction;
use App\Modules\YouTube\Analytics\YouTubeAnalyticsReportBuilder;
use App\Modules\YouTube\Core\Contracts\YouTubeGatewayInterface;
use App\Modules\YouTube\DTO\Request\YouTubeAnalyticsLookupDTO;
use App\Modules\YouTube\Presenters\YouTubeChannelPresenter;
use App\Modules\YouTube\Presenters\YouTubeVideoPresenter;
use App\Modules\YouTube\Support\YouTubeChannelInputNormalizer;
use Illuminate\Support\Arr;
use RuntimeException;

class AnalyticsSummaryAction extends AbstractYouTubeAction
{
    public function __construct(
        YouTubeGatewayInterface $gateway,
        private readonly YouTubeAnalyticsReportBuilder $reportBuilder,
        private readonly YouTubeVideoPresenter $videoPresenter,
        private readonly YouTubeChannelPresenter $channelPresenter,
        private readonly YouTubeChannelInputNormalizer $channelInputNormalizer,
    ) {
        parent::__construct($gateway);
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(YouTubeAnalyticsLookupDTO $query): array
    {
        $params = $query->toArray();

        if ($params['mode'] === 'channel' || $params['channelId'] !== '') {
            return $this->channelSummary($params['channelId'], $params['limit']);
        }

        return $this->videoSummary($params['videoId']);
    }

    /**
     * @return array<string, mixed>
     */
    private function videoSummary(string $videoId): array
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

    /**
     * @return array<string, mixed>
     */
    private function channelSummary(string $channelId, int $limit): array
    {
        $resolvedChannelId = $this->resolveChannelId($channelId);
        $videos = array_values($this->latestChannelVideos($resolvedChannelId, min(50, max(1, $limit))));

        return $this->summaryPayload(
            mode: 'channel',
            videos: $videos,
            video: null,
            channel: $this->channelsById([$resolvedChannelId])[$resolvedChannelId] ?? null,
            channelId: $resolvedChannelId,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $videos
     * @return array<string, mixed>
     */
    private function summaryPayload(
        string $mode,
        array $videos,
        ?array $video,
        ?array $channel,
        ?string $channelId = null,
    ): array {
        return [
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
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptySummary(string $mode): array
    {
        return [
            'mode' => $mode,
            'video' => null,
            'channel' => null,
            'totals' => $this->reportBuilder->totals([]),
            'distribution' => $this->reportBuilder->distribution([]),
            'leaders' => $this->reportBuilder->leaders([]),
            'insights' => [],
            'topTags' => [],
            'topVideos' => [],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function latestChannelVideos(string $channelId, int $limit): array
    {
        if ($channelId === '') {
            return [];
        }

        $search = $this->gateway->search([
            'channelId' => $channelId,
            'type' => 'video',
            'order' => 'date',
            'maxResults' => $limit,
        ]);

        $videoIds = collect($search['items'] ?? [])
            ->map(fn (array $item): ?string => Arr::get($item, 'id.videoId'))
            ->filter()
            ->values()
            ->all();

        return $this->videosById($videoIds);
    }

    private function resolveChannelId(string $channelInput): string
    {
        $channelInput = trim($channelInput);

        if ($this->channelInputNormalizer->looksLikeChannelId($channelInput)) {
            return $channelInput;
        }

        $byHandle = $this->firstChannelId([
            'forHandle' => $this->channelInputNormalizer->normalizeHandle($channelInput),
        ]);

        if ($byHandle !== null) {
            return $byHandle;
        }

        $byUsername = $this->firstChannelId([
            'forUsername' => $this->channelInputNormalizer->normalizeUsername($channelInput),
        ]);

        if ($byUsername !== null) {
            return $byUsername;
        }

        throw new RuntimeException('YouTube channel not found. Use a channel ID like UC..., @handle, or legacy username.', 404);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function firstChannelId(array $params): ?string
    {
        if (trim((string) reset($params)) === '') {
            return null;
        }

        $payload = $this->gateway->channels([
            ...$params,
            'part' => 'id',
            'maxResults' => 1,
        ]);

        $id = Arr::get($payload, 'items.0.id');

        return is_string($id) && $id !== '' ? $id : null;
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
}
