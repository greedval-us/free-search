<?php

namespace App\Modules\EmailIntel\Domain\DTO;

final readonly class EmailIntelResultDTO
{
    /**
     * @param array<string, mixed> $target
     * @param array<string, mixed> $dns
     * @param array<string, mixed> $profile
     * @param array<string, mixed> $analytics
     * @param array<int, array<string, string>> $signals
     */
    public function __construct(
        public string $checkedAt,
        public array $target,
        public array $dns,
        public array $profile,
        public array $analytics,
        public int $riskScore,
        public string $riskLevel,
        public array $signals,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'checkedAt' => $this->checkedAt,
            'target' => $this->target,
            'dns' => $this->dns,
            'profile' => $this->profile,
            'analytics' => $this->analytics,
            'riskScore' => $this->riskScore,
            'riskLevel' => $this->riskLevel,
            'signals' => $this->signals,
        ];
    }
}
