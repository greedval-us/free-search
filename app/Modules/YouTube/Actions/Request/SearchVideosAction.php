<?php

namespace App\Modules\YouTube\Actions\Request;

use App\Modules\YouTube\Actions\AbstractYouTubeAction;
use App\Modules\YouTube\Core\Contracts\YouTubeGatewayInterface;
use App\Modules\YouTube\DTO\Request\YouTubeSearchQueryDTO;
use App\Modules\YouTube\DTO\Result\YouTubeSearchResultDTO;
use App\Modules\YouTube\Presenters\YouTubeSearchItemPresenter;
use App\Modules\YouTube\Presenters\YouTubeVideoPresenter;
use App\Modules\YouTube\Support\YouTubeChannelResolver;
use Illuminate\Support\Arr;

class SearchVideosAction extends AbstractYouTubeAction
{
    public function __construct(
        YouTubeGatewayInterface $gateway,
        private readonly YouTubeSearchItemPresenter $searchItemPresenter,
        private readonly YouTubeVideoPresenter $videoPresenter,
        private readonly YouTubeChannelResolver $channelResolver,
    ) {
        parent::__construct($gateway);
    }

    public function handle(YouTubeSearchQueryDTO $query): YouTubeSearchResultDTO
    {
        $params = $query->toArray();
        $params = $this->resolveChannelFilter($params);
        $type = (string) ($params['type'] ?? 'video');
        $payload = $this->gateway->search($params);
        $detailsById = $type === 'video'
            ? $this->videosById($this->extractVideoIds($payload))
            : [];

        return new YouTubeSearchResultDTO([
            'items' => collect($payload['items'] ?? [])
                ->map(fn (array $item): array => $this->searchItemPresenter->present($item, $detailsById, $type))
                ->values()
                ->all(),
            'pagination' => [
                'nextPageToken' => $payload['nextPageToken'] ?? null,
                'prevPageToken' => $payload['prevPageToken'] ?? null,
                'total' => (int) Arr::get($payload, 'pageInfo.totalResults', 0),
                'perPage' => (int) Arr::get($payload, 'pageInfo.resultsPerPage', 0),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function resolveChannelFilter(array $params): array
    {
        $channelInput = trim((string) ($params['channelId'] ?? ''));

        if ($channelInput === '') {
            return $params;
        }

        $params['channelId'] = $this->channelResolver->resolve($channelInput);

        return $params;
    }

    /**
     * @return array<int, string>
     */
    private function extractVideoIds(array $payload): array
    {
        return collect($payload['items'] ?? [])
            ->map(fn (array $item): ?string => Arr::get($item, 'id.videoId'))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $videoIds
     * @return array<string, array<string, mixed>>
     */
    private function videosById(array $videoIds): array
    {
        if ($videoIds === []) {
            return [];
        }

        $payload = $this->gateway->videos([
            'id' => implode(',', array_unique($videoIds)),
            'maxResults' => 50,
        ]);

        return collect($payload['items'] ?? [])
            ->mapWithKeys(fn (array $item): array => [$item['id'] => $this->videoPresenter->present($item)])
            ->all();
    }
}
