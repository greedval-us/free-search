<?php

namespace App\Modules\Bluesky\Search;

use App\Modules\Bluesky\Actions\Request\SearchContentAction;
use App\Modules\Bluesky\Actions\Request\LoadPostLikesAction;
use App\Modules\Bluesky\Actions\Request\LoadPostRepostsAction;
use App\Modules\Bluesky\Actions\Request\LoadPostThreadAction;
use App\Modules\Bluesky\Actions\Request\LoadAuthorFeedAction;
use App\Modules\Bluesky\Actions\Request\LoadActorFollowersAction;
use App\Modules\Bluesky\Actions\Request\LoadActorFollowsAction;
use App\Modules\Bluesky\DTO\Request\BlueskySearchQueryDTO;
use App\Modules\Bluesky\DTO\Result\BlueskyActorListResultDTO;
use App\Modules\Bluesky\DTO\Result\BlueskyPostListResultDTO;
use App\Modules\Bluesky\DTO\Result\BlueskySearchResultDTO;
use App\Modules\Bluesky\DTO\Result\BlueskyThreadResultDTO;
use App\Modules\Bluesky\Search\Contracts\BlueskySearchApplicationServiceInterface;

final class BlueskySearchApplicationService implements BlueskySearchApplicationServiceInterface
{
    public function __construct(
        private readonly SearchContentAction $searchContentAction,
        private readonly LoadPostLikesAction $loadPostLikesAction,
        private readonly LoadPostRepostsAction $loadPostRepostsAction,
        private readonly LoadPostThreadAction $loadPostThreadAction,
        private readonly LoadAuthorFeedAction $loadAuthorFeedAction,
        private readonly LoadActorFollowersAction $loadActorFollowersAction,
        private readonly LoadActorFollowsAction $loadActorFollowsAction,
    ) {
    }

    public function search(BlueskySearchQueryDTO $query): BlueskySearchResultDTO
    {
        return $this->searchContentAction->handle($query);
    }

    public function likes(string $uri, ?string $cid, int $limit, ?string $cursor = null): BlueskyActorListResultDTO
    {
        return $this->loadPostLikesAction->handle($uri, $cid, $limit, $cursor);
    }

    public function reposts(string $uri, int $limit, ?string $cursor = null): BlueskyActorListResultDTO
    {
        return $this->loadPostRepostsAction->handle($uri, $limit, $cursor);
    }

    public function thread(string $uri, int $depth = 6, int $parentHeight = 6): BlueskyThreadResultDTO
    {
        return $this->loadPostThreadAction->handle($uri, $depth, $parentHeight);
    }

    public function authorFeed(string $actor, int $limit, ?string $cursor = null, ?string $filter = null): BlueskyPostListResultDTO
    {
        return $this->loadAuthorFeedAction->handle($actor, $limit, $cursor, $filter);
    }

    public function followers(string $actor, int $limit, ?string $cursor = null): BlueskyActorListResultDTO
    {
        return $this->loadActorFollowersAction->handle($actor, $limit, $cursor);
    }

    public function follows(string $actor, int $limit, ?string $cursor = null): BlueskyActorListResultDTO
    {
        return $this->loadActorFollowsAction->handle($actor, $limit, $cursor);
    }
}
