<?php

namespace App\Services\Access\DTO;

final readonly class FeatureAccessRequest
{
    public function __construct(
        public string $resource,
        public bool $consume,
        public bool $counts,
    ) {}
}
