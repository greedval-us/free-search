<?php

namespace App\Modules\Mastodon\Core\Contracts;

interface MastodonGatewayInterface
{
    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function search(array $params): array;
}
