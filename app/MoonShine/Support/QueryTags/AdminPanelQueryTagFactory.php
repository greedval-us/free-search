<?php

declare(strict_types=1);

namespace App\MoonShine\Support\QueryTags;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use MoonShine\Laravel\QueryTags\QueryTag;

final class AdminPanelQueryTagFactory
{
    /**
     * @param Closure(Builder): Builder $scope
     */
    public function all(Closure $scope): QueryTag
    {
        return QueryTag::make(__('admin_panel.tags.all'), $scope)
            ->default()
            ->icon('list-bullet');
    }

    public function today(string $column): QueryTag
    {
        return QueryTag::make(
            __('admin_panel.tags.today'),
            static fn (Builder $query): Builder => $query->whereDate($column, now()->toDateString())
        )->icon('calendar-days');
    }

    public function last24Hours(string $column): QueryTag
    {
        return QueryTag::make(
            __('admin_panel.tags.last_24h'),
            static fn (Builder $query): Builder => $query->where($column, '>=', now()->subDay())
        )->icon('clock');
    }
}

