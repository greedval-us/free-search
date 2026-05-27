<?php

declare(strict_types=1);

namespace App\MoonShine\Support;

use App\Models\User;
use App\Services\Access\AccountAccessSummaryService;

final readonly class UserQuotaSummaryFormatter
{
    public function __construct(
        private AccountAccessSummaryService $accessSummaryService = new AccountAccessSummaryService,
    ) {}

    public function format(User $user): string
    {
        $features = $this->accessSummaryService->forUser($user)['features'] ?? [];

        $resources = config('access.resources', []);
        if (! is_array($resources)) {
            return '-';
        }

        $lines = [];

        foreach (array_keys($resources) as $resource) {
            $resource = (string) $resource;
            $lines[] = $this->formatFeature(
                $this->labelFor($resource),
                $features[$resource] ?? null,
            );
        }

        return implode('; ', $lines);
    }

    /**
     * @param  array{limit?: int, remaining?: int}|null  $feature
     */
    private function formatFeature(string $label, ?array $feature): string
    {
        $limit = max(0, (int) ($feature['limit'] ?? 0));
        $remaining = max(0, (int) ($feature['remaining'] ?? 0));

        return "{$label}: {$remaining}/{$limit}";
    }

    private function labelFor(string $resource): string
    {
        $key = "admin_panel.quota_resources.{$resource}";
        $label = __($key);

        return $label === $key ? $resource : $label;
    }
}
