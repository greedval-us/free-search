<?php

namespace App\Modules\Bluesky\Core\Contracts;

interface BlueskyGatewayInterface
{
    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function searchPosts(array $params): array;

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function searchActors(array $params): array;

    /**
     * @param array<int, string> $actors
     * @return array<string, mixed>
     */
    public function getProfiles(array $actors): array;

    /**
     * @return array<string, mixed>
     */
    public function getLikes(string $uri, ?string $cid, int $limit, ?string $cursor = null): array;

    /**
     * @return array<string, mixed>
     */
    public function getRepostedBy(string $uri, int $limit, ?string $cursor = null): array;

    /**
     * @return array<string, mixed>
     */
    public function getPostThread(string $uri, int $depth = 6, int $parentHeight = 6): array;

    /**
     * @return array<string, mixed>
     */
    public function getAuthorFeed(string $actor, int $limit, ?string $cursor = null, ?string $filter = null): array;

    /**
     * @return array<string, mixed>
     */
    public function getFollowers(string $actor, int $limit, ?string $cursor = null): array;

    /**
     * @return array<string, mixed>
     */
    public function getFollows(string $actor, int $limit, ?string $cursor = null): array;
}
