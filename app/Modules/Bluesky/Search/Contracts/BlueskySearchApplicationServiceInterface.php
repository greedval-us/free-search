<?php

namespace App\Modules\Bluesky\Search\Contracts;

use App\Modules\Bluesky\DTO\Request\BlueskySearchQueryDTO;
use App\Modules\Bluesky\DTO\Result\BlueskyActorListResultDTO;
use App\Modules\Bluesky\DTO\Result\BlueskyPostListResultDTO;
use App\Modules\Bluesky\DTO\Result\BlueskySearchResultDTO;
use App\Modules\Bluesky\DTO\Result\BlueskyThreadResultDTO;

interface BlueskySearchApplicationServiceInterface
{
    public function search(BlueskySearchQueryDTO $query): BlueskySearchResultDTO;

    public function likes(string $uri, ?string $cid, int $limit, ?string $cursor = null): BlueskyActorListResultDTO;

    public function reposts(string $uri, int $limit, ?string $cursor = null): BlueskyActorListResultDTO;

    public function thread(string $uri, int $depth = 6, int $parentHeight = 6): BlueskyThreadResultDTO;

    public function authorFeed(string $actor, int $limit, ?string $cursor = null, ?string $filter = null): BlueskyPostListResultDTO;

    public function followers(string $actor, int $limit, ?string $cursor = null): BlueskyActorListResultDTO;

    public function follows(string $actor, int $limit, ?string $cursor = null): BlueskyActorListResultDTO;
}
