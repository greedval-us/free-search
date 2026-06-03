<?php

namespace App\Modules\Bluesky\Actions\Request;

use App\Modules\Bluesky\Actions\AbstractBlueskyAction;
use App\Modules\Bluesky\DTO\Result\BlueskyThreadResultDTO;
use App\Modules\Bluesky\Presenters\BlueskyThreadPresenter;

final class LoadPostThreadAction extends AbstractBlueskyAction
{
    public function __construct(
        \App\Modules\Bluesky\Core\Contracts\BlueskyGatewayInterface $gateway,
        private readonly BlueskyThreadPresenter $threadPresenter,
    ) {
        parent::__construct($gateway);
    }

    public function handle(string $uri, int $depth = 6, int $parentHeight = 6): BlueskyThreadResultDTO
    {
        $payload = $this->gateway->getPostThread($uri, $depth, $parentHeight);

        return $this->threadPresenter->present($payload, $uri, $depth, $parentHeight);
    }
}
