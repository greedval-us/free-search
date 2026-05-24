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
        return $this->make(__('admin_panel.tags.all'), $scope, 'list-bullet', true);
    }

    /**
     * @param Closure(Builder): Builder $scope
     */
    public function make(string $label, Closure $scope, ?string $icon = null, bool $default = false): QueryTag
    {
        $tag = QueryTag::make($label, $scope);

        if ($default) {
            $tag->default();
        }

        if ($icon !== null && $icon !== '') {
            $tag->icon($icon);
        }

        return $tag;
    }

    public function today(string $column): QueryTag
    {
        return $this->make(
            __('admin_panel.tags.today'),
            static fn (Builder $query): Builder => $query->whereDate($column, now()->toDateString()),
            'calendar-days'
        );
    }

    public function last24Hours(string $column): QueryTag
    {
        return $this->make(
            __('admin_panel.tags.last_24h'),
            static fn (Builder $query): Builder => $query->where($column, '>=', now()->subDay()),
            'clock'
        );
    }
}
