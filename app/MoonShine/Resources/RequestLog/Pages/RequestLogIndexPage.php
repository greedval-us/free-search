<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\RequestLog\Pages;

use App\Models\User;
use App\MoonShine\Resources\AppUser\AppUserResource;
use App\MoonShine\Resources\RequestLog\RequestLogResource;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\IndexPage;
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
        return [
            ID::make()->sortable(),
            Date::make('Date', 'created_at')
                ->format('d.m.Y H:i:s')
                ->sortable(),
            BelongsTo::make(
                'User',
                'user',
                formatted: static fn (?User $model): string => $model?->email ?? '-',
                resource: AppUserResource::class,
            ),
            Text::make('Method', 'method')->sortable(),
            Number::make('Status', 'status_code')->sortable(),
            Number::make('Response ms', 'response_time')->sortable(),
            Text::make('Module', 'module_key')->sortable(),
            Text::make('Action', 'action_key')->sortable(),
            Text::make('Query', 'query_preview'),
            Text::make('Route', 'route_name'),
        ];
    }

    protected function filters(): iterable
    {
        return [
            BelongsTo::make(
                'User',
                'user',
                formatted: static fn (User $model): string => $model->email,
                resource: AppUserResource::class,
            )->valuesQuery(static fn (Builder $q): Builder => $q->select(['id', 'email'])),
            Select::make('Method', 'method')->options([
                'GET' => 'GET',
                'POST' => 'POST',
                'PUT' => 'PUT',
                'PATCH' => 'PATCH',
                'DELETE' => 'DELETE',
            ]),
            Text::make('Module', 'module_key'),
            Text::make('Action', 'action_key'),
            Number::make('Status', 'status_code'),
        ];
    }
}
