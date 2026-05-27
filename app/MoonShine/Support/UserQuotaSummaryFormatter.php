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

        $groups = [];

        foreach ($resources as $resource => $config) {
            $resource = (string) $resource;
            $config = is_array($config) ? $config : [];
            $module = (string) ($config['module'] ?? strtok($resource, '.') ?: $resource);
            $capability = (string) ($config['capability'] ?? str($resource)->after('.'));

            $groups[$module][] = $this->formatFeature(
                $this->capabilityLabel($capability),
                $features[$resource] ?? null,
            );
        }

        $lines = [];

        foreach ($groups as $module => $items) {
            $lines[] = $this->moduleLabel((string) $module).': '.implode(', ', $items);
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

    private function moduleLabel(string $module): string
    {
        $key = "admin_panel.quota_modules.{$module}";
        $label = __($key);

        return $label === $key ? $module : $label;
    }

    private function capabilityLabel(string $capability): string
    {
        $key = "admin_panel.quota_capabilities.{$capability}";
        $label = __($key);

        return $label === $key ? $capability : $label;
    }
}
