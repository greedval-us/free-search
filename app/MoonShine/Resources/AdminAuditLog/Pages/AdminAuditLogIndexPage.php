<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\AdminAuditLog\Pages;

use App\MoonShine\Resources\AdminAuditLog\AdminAuditLogResource;
use App\MoonShine\Support\Formatting\AdminAuditChangeFormatter;
use App\MoonShine\Support\Formatting\AdminPanelDateFormatter;
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
        $formatter = new AdminAuditChangeFormatter();

        return [
            ID::make()->sortable(),
            Date::make(__('admin_panel.fields.date'), 'created_at')
                ->format(AdminPanelDateFormatter::DATE_TIME_FORMAT)
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
                static fn (mixed $original): string => $formatter->changedKeysSummary($original->changes ?? null),
            ),
            Text::make(
                __('admin_panel.fields.change_details'),
                'changes',
                static fn (mixed $original): string => $formatter->changeDetailsSummary($original->changes ?? null),
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

}
