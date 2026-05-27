<?php

namespace App\Services\Access\DTO;

final readonly class FeatureAccessDecision
{
    public function __construct(
        public bool $allowed,
        public string $feature,
        public string $plan,
        public int $limit,
        public int $used,
        public bool $counts,
        public ?string $message = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toMeta(): array
    {
        return [
            'feature' => $this->feature,
            'plan' => $this->plan,
            'limit' => $this->limit,
            'used' => $this->used,
            'remaining' => max(0, $this->limit - $this->used),
            'counts' => $this->counts,
        ];
    }
}
