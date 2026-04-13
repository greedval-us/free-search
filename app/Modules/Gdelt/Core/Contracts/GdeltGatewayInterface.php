<?php

namespace App\Modules\Gdelt\Core\Contracts;

use App\Modules\Gdelt\DTO\Request\GdeltSearchQueryDTO;

interface GdeltGatewayInterface
{
    /**
     * @return array<string, mixed>
     */
    public function searchArticles(GdeltSearchQueryDTO $query): array;
}
