<?php

namespace App\Services\Access\DTO;

final readonly class FeatureUsageReceipt
{
    public function __construct(
        public string $id,
        public string $usageDate,
        public int $used,
    ) {}
}
