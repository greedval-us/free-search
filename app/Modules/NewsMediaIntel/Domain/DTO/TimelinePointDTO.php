<?php

namespace App\Modules\NewsMediaIntel\Domain\DTO;

final class TimelinePointDTO
{
    public function __construct(
        public readonly string $date,
        public readonly int $mentions,
    ) {
    }

    /**
     * @return array{date: string, mentions: int}
     */
    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'mentions' => $this->mentions,
        ];
    }
}

