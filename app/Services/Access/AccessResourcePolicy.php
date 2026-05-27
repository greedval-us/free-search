<?php

namespace App\Services\Access;

final readonly class AccessResourcePolicy
{
    public function __construct(
        public string $resource,
        public string $quotaKey,
        public bool $counts,
    ) {}
}
