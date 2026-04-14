<?php

namespace App\Modules\Username\DTO;

final class UsernameSearchResultDTO
{
    /**
     * @param array<int, UsernameSourceCheckResultDTO> $items
     */
    public function __construct(
        public readonly string $username,
        public readonly string $checkedAt,
        public readonly UsernameSearchSummaryDTO $summary,
        public readonly array $items,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'username' => $this->username,
            'checkedAt' => $this->checkedAt,
            'summary' => $this->summary->toArray(),
            'items' => array_map(
                static fn (UsernameSourceCheckResultDTO $item): array => $item->toArray(),
                $this->items
            ),
        ];
    }
}
