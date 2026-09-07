<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Shared\Pages;

use App\MoonShine\Support\AdminNavigationCatalog;
use App\MoonShine\Support\Formatting\AdminPanelDateFormatter;
use App\MoonShine\Support\QueryTags\AdminPanelQueryTagFactory;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\UI\Components\Table\TableBuilder;

abstract class AdminIndexPage extends IndexPage
{
    public function getSubtitle(): string
    {
        $key = AdminNavigationCatalog::resourceKey($this->getResource()::class);
        $translationKey = "admin_panel.descriptions.{$key}";
        $subtitle = __($translationKey);

        return $subtitle === $translationKey ? '' : $subtitle;
    }

    protected function modifyListComponent(ComponentContract $component): TableBuilder
    {
        return $component
            ->columnSelection()
            ->sticky()
            ->stickyButtons();
    }

    protected function adminDateTimeFormat(): string
    {
        return AdminPanelDateFormatter::DATE_TIME_FORMAT;
    }

    /**
     * @param  Closure(Builder): Builder  $scope
     */
    protected function allTag(Closure $scope): QueryTag
    {
        return $this->queryTagFactory()->all($scope);
    }

    protected function todayTag(string $column): QueryTag
    {
        return $this->queryTagFactory()->today($column);
    }

    protected function last24HoursTag(string $column): QueryTag
    {
        return $this->queryTagFactory()->last24Hours($column);
    }

    /**
     * @param  Closure(Builder): Builder  $scope
     */
    protected function customTag(string $label, Closure $scope, ?string $icon = null, bool $default = false): QueryTag
    {
        return $this->queryTagFactory()->make($label, $scope, $icon, $default);
    }

    protected function queryTagFactory(): AdminPanelQueryTagFactory
    {
        return new AdminPanelQueryTagFactory;
    }
}
