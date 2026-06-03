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
}
