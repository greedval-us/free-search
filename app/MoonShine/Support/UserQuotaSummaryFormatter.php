<?php

declare(strict_types=1);

namespace App\MoonShine\Support;

use App\Models\User;
use App\Services\Access\AccountAccessSummaryService;
use Illuminate\Support\HtmlString;

final readonly class UserQuotaSummaryFormatter
{
    public function __construct(
        private AccountAccessSummaryService $accessSummaryService = new AccountAccessSummaryService,
    ) {}

    public function format(User $user): string
    {
        return strip_tags((string) $this->forDetails($user));
    }

    public function forIndex(User $user): HtmlString
    {
        return new HtmlString($this->render($user, true));
    }

    public function forDetails(User $user): HtmlString
    {
        return new HtmlString($this->render($user, false));
    }

    private function render(User $user, bool $compact): string
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

            $groups[$module][] = $this->featureView(
                capability: $capability,
                label: $this->capabilityLabel($capability),
                feature: $features[$resource] ?? null,
                compact: $compact,
            );
        }

        $lines = [];

        foreach ($groups as $module => $items) {
            $lines[] = $compact
                ? $this->compactModule((string) $module, $items)
                : $this->detailModule((string) $module, $items);
        }

        return $compact
            ? '<div style="display:grid;gap:4px;min-width:220px;max-width:280px">'.implode('', $lines).'</div>'
            : '<div style="display:grid;gap:8px;max-width:520px">'.implode('', $lines).'</div>';
    }

    /**
     * @param  array{limit?: int, remaining?: int}|null  $feature
     */
    private function featureView(string $capability, string $label, ?array $feature, bool $compact): array
    {
        $limit = max(0, (int) ($feature['limit'] ?? 0));
        $remaining = max(0, (int) ($feature['remaining'] ?? 0));
        $value = "{$remaining}/{$limit}";

        return [
            'label' => e($label),
            'short' => e($this->shortCapabilityLabel($capability)),
            'value' => e($value),
            'state' => $this->state($remaining, $limit),
            'compact' => $compact,
        ];
    }

    /**
     * @param  list<array{label: string, short: string, value: string, state: string, compact: bool}>  $items
     */
    private function compactModule(string $module, array $items): string
    {
        $badges = array_map(
            static fn (array $item): string => sprintf(
                '<span title="%s" style="display:inline-flex;align-items:center;gap:4px;border-radius:999px;border:1px solid %s;background:%s;padding:2px 7px;font-size:11px;line-height:1.35;white-space:nowrap"><span style="color:#64748b">%s</span><strong>%s</strong></span>',
                $item['label'],
                self::borderColor($item['state']),
                self::backgroundColor($item['state']),
                $item['short'],
                $item['value'],
            ),
            $items,
        );

        return sprintf(
            '<div style="display:grid;gap:3px"><div style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase">%s</div><div style="display:flex;flex-wrap:wrap;gap:4px">%s</div></div>',
            e($this->moduleLabel($module)),
            implode('', $badges),
        );
    }

    /**
     * @param  list<array{label: string, short: string, value: string, state: string, compact: bool}>  $items
     */
    private function detailModule(string $module, array $items): string
    {
        $rows = array_map(
            static fn (array $item): string => sprintf(
                '<div style="display:flex;align-items:center;justify-content:space-between;gap:16px;border-top:1px solid rgba(148,163,184,.25);padding-top:6px"><span>%s</span><strong style="color:%s">%s</strong></div>',
                $item['label'],
                self::textColor($item['state']),
                $item['value'],
            ),
            $items,
        );

        return sprintf(
            '<section style="border:1px solid rgba(148,163,184,.35);border-radius:8px;padding:8px 10px"><div style="font-weight:600;margin-bottom:2px">%s</div><div style="display:grid;gap:6px;font-size:13px">%s</div></section>',
            e($this->moduleLabel($module)),
            implode('', $rows),
        );
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

    private function shortCapabilityLabel(string $capability): string
    {
        $key = "admin_panel.quota_capability_short.{$capability}";
        $label = __($key);

        return $label === $key ? strtoupper(substr($capability, 0, 1)) : $label;
    }

    private function state(int $remaining, int $limit): string
    {
        if ($limit <= 0) {
            return 'disabled';
        }

        if ($remaining <= 0) {
            return 'empty';
        }

        if ($remaining <= max(1, (int) floor($limit * 0.2))) {
            return 'low';
        }

        return 'ok';
    }

    private static function borderColor(string $state): string
    {
        return match ($state) {
            'empty' => 'rgba(239,68,68,.55)',
            'low' => 'rgba(245,158,11,.55)',
            'disabled' => 'rgba(148,163,184,.35)',
            default => 'rgba(34,197,94,.45)',
        };
    }

    private static function backgroundColor(string $state): string
    {
        return match ($state) {
            'empty' => 'rgba(239,68,68,.10)',
            'low' => 'rgba(245,158,11,.12)',
            'disabled' => 'rgba(148,163,184,.08)',
            default => 'rgba(34,197,94,.10)',
        };
    }

    private static function textColor(string $state): string
    {
        return match ($state) {
            'empty' => '#dc2626',
            'low' => '#d97706',
            'disabled' => '#64748b',
            default => '#16a34a',
        };
    }
}
