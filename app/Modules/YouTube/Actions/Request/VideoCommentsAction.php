<?php

namespace App\Modules\YouTube\Actions\Request;

use App\Modules\YouTube\Actions\AbstractYouTubeAction;
use App\Modules\YouTube\Core\Contracts\YouTubeGatewayInterface;
use App\Modules\YouTube\DTO\Request\YouTubeCommentsQueryDTO;
use App\Modules\YouTube\Presenters\YouTubeCommentThreadPresenter;
use Illuminate\Support\Arr;

class VideoCommentsAction extends AbstractYouTubeAction
{
    public function __construct(
        YouTubeGatewayInterface $gateway,
        private readonly YouTubeCommentThreadPresenter $commentThreadPresenter,
    ) {
        parent::__construct($gateway);
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(YouTubeCommentsQueryDTO $query): array
    {
        $payload = $this->gateway->commentThreads($query->toArray());

        return [
            'items' => collect($payload['items'] ?? [])
                ->map(fn (array $item): array => $this->commentThreadPresenter->present($item))
                ->values()
                ->all(),
            'pagination' => [
                'nextPageToken' => $payload['nextPageToken'] ?? null,
                'total' => (int) Arr::get($payload, 'pageInfo.totalResults', 0),
                'perPage' => (int) Arr::get($payload, 'pageInfo.resultsPerPage', 0),
            ],
        ];
    }
}
