<?php

namespace App\Modules\Fio\Domain\DTO;

final class FioClusterDTO
{
    public function __construct(
        public readonly string $key,
        public readonly int $count,
        public readonly float $percent,
    ) {
    }

    /**
     * @return array{key: string, count: int, percent: float}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'count' => $this->count,
            'percent' => $this->percent,
        ];
    }
}
