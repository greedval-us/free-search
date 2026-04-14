<?php

namespace App\Modules\Username\DTO;

use App\Modules\Username\Enums\UsernameSearchStatus;

final class UsernameSearchSummaryDTO
{
    public function __construct(
        public readonly int $checked,
        public readonly int $found,
        public readonly int $notFound,
        public readonly int $unknown,
    ) {
    }

    /**
     * @param array<int, UsernameSourceCheckResultDTO> $results
     */
    public static function fromResults(array $results): self
    {
        $found = 0;
        $notFound = 0;
        $unknown = 0;

        foreach ($results as $result) {
            if ($result->status === UsernameSearchStatus::Found) {
                $found++;
                continue;
            }

            if ($result->status === UsernameSearchStatus::NotFound) {
                $notFound++;
                continue;
            }

            $unknown++;
        }

        return new self(
            checked: count($results),
            found: $found,
            notFound: $notFound,
            unknown: $unknown,
        );
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'checked' => $this->checked,
            'found' => $this->found,
            'notFound' => $this->notFound,
            'unknown' => $this->unknown,
        ];
    }
}
