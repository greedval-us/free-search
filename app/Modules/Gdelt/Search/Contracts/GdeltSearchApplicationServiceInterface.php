<?php

namespace App\Modules\Gdelt\Search\Contracts;

use App\Modules\Gdelt\DTO\Request\GdeltSearchQueryDTO;
use App\Modules\Gdelt\DTO\Result\GdeltSearchResultDTO;

interface GdeltSearchApplicationServiceInterface
{
    public function search(GdeltSearchQueryDTO $query): GdeltSearchResultDTO;
}
