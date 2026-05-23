<?php

namespace App\Modules\Username\Application\Contracts;

use App\Modules\Username\Domain\DTO\UsernameSearchQueryDTO;
use App\Modules\Username\Domain\DTO\UsernameSearchResultDTO;

interface UsernameSearchServiceInterface
{
    public function search(UsernameSearchQueryDTO $query): UsernameSearchResultDTO;
}

