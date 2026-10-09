<?php

namespace App\Services\Access;

use App\Exceptions\FeatureAccessDeniedException;
use App\Models\User;
use App\Services\Access\Contracts\FeatureAccessServiceInterface;
use App\Services\Access\DTO\FeatureAccessDecision;

final readonly class SiteIntelReportAccess
{
    private const RESOURCES = [
        'analytics' => 'site-intel.analytics',
        'seo-audit' => 'site-intel.seo-audit',
    ];

    public function __construct(private FeatureAccessServiceInterface $featureAccess) {}

    /** @return list<string> */
    public function availableTypes(User $user): array
    {
        return array_values(array_filter(
            array_keys(self::RESOURCES),
            fn (string $type): bool => $this->allows($user, $type),
        ));
    }

    public function allows(User $user, string $type): bool
    {
        return $this->decision($user, $type)->allowed;
    }

    public function ensure(User $user, string $type): void
    {
        $decision = $this->decision($user, $type);
        if (! $decision->allowed) {
            throw new FeatureAccessDeniedException($decision);
        }
    }

    public function assertAny(User $user): void
    {
        foreach (array_keys(self::RESOURCES) as $type) {
            if ($this->allows($user, $type)) {
                return;
            }
        }

        $this->ensure($user, 'analytics');
    }

    private function decision(User $user, string $type): FeatureAccessDecision
    {
        $resource = self::RESOURCES[$type] ?? null;
        if ($resource === null) {
            return new FeatureAccessDecision(
                allowed: false,
                feature: 'site-intel.reports',
                plan: $user->currentPlan()->value,
                limit: 0,
                used: 0,
                counts: false,
            );
        }

        return $this->featureAccess->inspect($user, $resource, false);
    }
}
