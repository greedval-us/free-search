<?php

namespace App\Modules\Fio\Application\Contracts;

use App\Modules\Fio\Domain\DTO\FioLookupResultDTO;

interface FioPublicSearchServiceInterface
{
    public function search(string $fullName, ?string $qualifier = null): FioLookupResultDTO;
}

