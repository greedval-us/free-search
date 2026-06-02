<?php

namespace App\Modules\Mastodon\Core\Contracts;

interface MastodonGatewayInterface
{
    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function search(array $params): array;

    /**
     * @return array<string, mixed>
     */
    public function context(string $statusId): array;

    /**
     * @return array<string, mixed>
     */
    public function accountStatuses(string $accountId, int $limit, ?string $maxId = null): array;

    /**
     * @return array<string, mixed>
     */
    public function accountFollowers(string $accountId, int $limit, ?string $maxId = null): array;
}
