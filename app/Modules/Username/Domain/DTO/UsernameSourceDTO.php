<?php

namespace App\Modules\Username\Domain\DTO;

final class UsernameSourceDTO
{
    /**
     * @param array<int, string> $notFoundMarkers
     */
    public function __construct(
        public readonly string $key,
        public readonly string $name,
        public readonly string $profileTemplate,
        public readonly string $regionGroup = 'global',
        public readonly string $primaryUsersRegion = 'global',
        public readonly array $notFoundMarkers = [],
        public readonly bool $strictProfileUri = false,
    ) {
    }
}
