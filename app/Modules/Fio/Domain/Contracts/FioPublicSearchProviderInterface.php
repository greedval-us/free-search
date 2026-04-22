<?php

namespace App\Modules\Fio\Domain\Contracts;

use App\Modules\Fio\Domain\DTO\PublicSearchEntryDTO;

interface FioPublicSearchProviderInterface
{
    /**
     * @return array<int, PublicSearchEntryDTO>
     */
    public function search(string $fullName): array;
}
