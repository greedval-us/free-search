<?php

declare(strict_types=1);

namespace App\MoonShine\Support\Formatting;

final class AdminAuditChangeFormatter
{
    public function changedKeysSummary(mixed $changes): string
    {
        if (!is_array($changes) || $changes === []) {
            return __('admin_panel.values.not_available');
        }

        $keys = array_keys($changes);

        return implode(', ', array_map(static fn (mixed $key): string => (string) $key, $keys));
    }

    public function changeDetailsSummary(mixed $changes): string
    {
        if (!is_array($changes) || $changes === []) {
            return __('admin_panel.values.not_available');
        }

        $parts = [];

        foreach ($changes as $field => $delta) {
            if (!is_array($delta)) {
                continue;
            }

            $old = $this->valueToString($delta['old'] ?? null);
            $new = $this->valueToString($delta['new'] ?? null);
            $parts[] = sprintf('%s: %s -> %s', (string) $field, $old, $new);
        }

        if ($parts === []) {
            return __('admin_panel.values.not_available');
        }

        return mb_strimwidth(implode(' | ', $parts), 0, 240, '...');
    }

    public function valueToString(mixed $value): string
    {
        if ($value === null) {
            return __('admin_panel.values.null');
        }

        if (is_bool($value)) {
            return $value ? __('admin_panel.values.true') : __('admin_panel.values.false');
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return is_string($json) ? $json : __('admin_panel.values.complex');
    }
}

