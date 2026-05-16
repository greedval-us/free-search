<?php

namespace App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel;

use App\Modules\NewsMediaIntel\Domain\DTO\NewsMentionDTO;

final class NewsMentionDeduplicator
{
    /**
     * @param array<int, NewsMentionDTO> $mentions
     * @return array<int, NewsMentionDTO>
     */
    public function deduplicate(array $mentions): array
    {
        $map = [];

        foreach ($mentions as $mention) {
            $key = mb_strtolower(trim($mention->link));
            if ($key === '' || isset($map[$key])) {
                continue;
            }

            $map[$key] = $mention;
        }

        return array_values($map);
    }
}

