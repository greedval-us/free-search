<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\FeatureUsageDaily\Pages;

use App\Models\User;
use App\MoonShine\Resources\AppUser\AppUserResource;
use App\MoonShine\Resources\FeatureUsageDaily\FeatureUsageDailyResource;
use App\MoonShine\Resources\Shared\Pages\AdminIndexPage;
use Illuminate\Database\Eloquent\Builder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;

/**
 * @extends AdminIndexPage<FeatureUsageDailyResource>
 */
final class FeatureUsageDailyIndexPage extends AdminIndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Date::make(__('admin_panel.fields.usage_date'), 'usage_date')
                ->format('d.m.Y')
                ->sortable(),
            BelongsTo::make(
                __('admin_panel.fields.user'),
                'user',
                formatted: static fn (?User $model): string => $model?->email ?? '-',
                resource: AppUserResource::class,
            ),
            Text::make(__('admin_panel.fields.feature'), 'feature')->sortable(),
            Number::make(__('admin_panel.fields.used'), 'used')->sortable(),
            Date::make(__('admin_panel.fields.updated_at'), 'updated_at')
                ->format('d.m.Y H:i')
                ->sortable(),
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
            Text::make(__('admin_panel.fields.feature'), 'feature'),
        ];
    }

    protected function queryTags(): array
    {
        return [
            $this->allTag(static fn (Builder $query): Builder => $query),
            $this->todayTag('usage_date'),
        ];
    }
}
