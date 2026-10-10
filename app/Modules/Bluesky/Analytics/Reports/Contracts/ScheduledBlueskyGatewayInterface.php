<?php

namespace App\Modules\Bluesky\Analytics\Reports\Contracts;

interface ScheduledBlueskyGatewayInterface
{
    public function getProfiles(array $actors): array;

    public function getAuthorFeed(string $actor, int $limit, ?string $cursor = null): array;
}
