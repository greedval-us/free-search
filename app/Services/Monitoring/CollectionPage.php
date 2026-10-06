<?php

namespace App\Services\Monitoring;

final readonly class CollectionPage
{
    public function __construct(public array $items, public ?string $cursor = null,
        public bool $complete = true, public array $warnings = [], public ?int $retryAfter = null) {}
}
