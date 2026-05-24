<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\AdminAuditLog\Pages;

use App\MoonShine\Resources\AdminAuditLog\AdminAuditLogResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;

/**
 * @extends IndexPage<AdminAuditLogResource>
 */
final class AdminAuditLogIndexPage extends IndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Date::make(__('admin_panel.fields.date'), 'created_at')
                ->format('d.m.Y H:i:s')
                ->sortable(),
            Text::make(__('admin_panel.fields.admin'), 'actor_admin_name'),
            Text::make(__('admin_panel.fields.action'), 'action')->sortable(),
            Text::make(__('admin_panel.fields.target'), 'target_type')->sortable(),
            Number::make(__('admin_panel.fields.target_id'), 'target_id')->sortable(),
            Number::make(
                __('admin_panel.fields.changed_fields'),
                'changes',
                static fn (mixed $original): int => is_array($original->changes ?? null)
                    ? count($original->changes)
                    : 0
            ),
            Text::make(
                __('admin_panel.fields.changed_keys'),
                'changes',
                static fn (mixed $original): string => self::changedKeysSummary($original->changes ?? null),
            ),
            Text::make(
                __('admin_panel.fields.change_details'),
                'changes',
                static fn (mixed $original): string => self::changeDetailsSummary($original->changes ?? null),
            ),
        ];
    }

    protected function filters(): iterable
    {
        return [
            Text::make(__('admin_panel.fields.admin'), 'actor_admin_name'),
            Text::make(__('admin_panel.fields.action'), 'action'),
            Text::make(__('admin_panel.fields.target'), 'target_type'),
            Number::make(__('admin_panel.fields.target_id'), 'target_id'),
        ];
    }

    private static function changedKeysSummary(mixed $changes): string
    {
        if (!is_array($changes) || $changes === []) {
            return __('admin_panel.values.not_available');
        }

        $keys = array_keys($changes);

        return implode(', ', array_map(static fn (mixed $key): string => (string) $key, $keys));
    }

    private static function changeDetailsSummary(mixed $changes): string
    {
        if (!is_array($changes) || $changes === []) {
            return __('admin_panel.values.not_available');
        }

        $parts = [];

        foreach ($changes as $field => $delta) {
            if (!is_array($delta)) {
                continue;
            }

            $old = self::valueToString($delta['old'] ?? null);
            $new = self::valueToString($delta['new'] ?? null);
            $parts[] = sprintf('%s: %s -> %s', (string) $field, $old, $new);
        }

        if ($parts === []) {
            return __('admin_panel.values.not_available');
        }

        $text = implode(' | ', $parts);

        return mb_strimwidth($text, 0, 240, '...');
    }

    private static function valueToString(mixed $value): string
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
