<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\RequestLog\Pages;

use App\Models\User;
use App\MoonShine\Resources\AppUser\AppUserResource;
use App\MoonShine\Resources\RequestLog\RequestLogResource;
use App\MoonShine\Support\Formatting\AdminPanelDateFormatter;
use App\MoonShine\Support\Formatting\RequestLogBadgeResolver;
use App\MoonShine\Support\QueryTags\AdminPanelQueryTagFactory;
use Illuminate\Database\Eloquent\Builder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\UI\Fields\Field;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;

/**
 * @extends IndexPage<RequestLogResource>
 */
final class RequestLogIndexPage extends IndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        $badgeResolver = new RequestLogBadgeResolver();

        return [
            ID::make()->sortable(),
            Date::make(__('admin_panel.fields.date'), 'created_at')
                ->format(AdminPanelDateFormatter::DATE_TIME_FORMAT)
                ->sortable(),
            BelongsTo::make(
                __('admin_panel.fields.user'),
                'user',
                formatted: static fn (?User $model): string => $model?->email ?? '-',
                resource: AppUserResource::class,
            ),
            Text::make(__('admin_panel.fields.method'), 'method')->sortable(),
            Number::make(__('admin_panel.fields.status'), 'status_code')
                ->sortable()
                ->badge(static fn (mixed $value, Field $field): string => $badgeResolver->statusColor((int) $value)),
            Number::make(__('admin_panel.fields.response_ms'), 'response_time')
                ->sortable()
                ->badge(static fn (mixed $value, Field $field): string => $badgeResolver->responseTimeColor((float) $value)),
            Text::make(__('admin_panel.fields.module'), 'module_key')->sortable(),
            Text::make(__('admin_panel.fields.action'), 'action_key')->sortable(),
            Text::make(__('admin_panel.fields.query'), 'query_preview'),
            Text::make(__('admin_panel.fields.path'), 'path'),
            Text::make(__('admin_panel.fields.route'), 'route_name'),
        ];
    }

    protected function filters(): iterable
    {
        return [
            BelongsTo::make(
                __('admin_panel.fields.user'),
                'user',
                formatted: static fn (User $model): string => $model->email,
                resource: AppUserResource::class,
            )->valuesQuery(static fn (Builder $q): Builder => $q->select(['id', 'email'])),
            Select::make(__('admin_panel.fields.method'), 'method')->options([
                'GET' => 'GET',
                'POST' => 'POST',
                'PUT' => 'PUT',
                'PATCH' => 'PATCH',
                'DELETE' => 'DELETE',
            ]),
            Text::make(__('admin_panel.fields.module'), 'module_key'),
            Text::make(__('admin_panel.fields.action'), 'action_key'),
            Number::make(__('admin_panel.fields.status'), 'status_code'),
        ];
    }

    protected function queryTags(): array
    {
        $tags = new AdminPanelQueryTagFactory();

        return [
            $tags->all(static fn (Builder $query): Builder => $query),
            QueryTag::make(__('admin_panel.tags.errors_4xx'), static fn (Builder $query): Builder => $query->whereBetween('status_code', [400, 499]))->icon('exclamation-triangle'),
            QueryTag::make(__('admin_panel.tags.errors_5xx'), static fn (Builder $query): Builder => $query->whereBetween('status_code', [500, 599]))->icon('x-circle'),
            QueryTag::make(__('admin_panel.tags.slow_1500'), static fn (Builder $query): Builder => $query->where('response_time', '>', 1500))->icon('clock'),
            $tags->today('created_at'),
        ];
    }
}
