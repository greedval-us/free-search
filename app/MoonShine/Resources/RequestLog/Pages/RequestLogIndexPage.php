<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\RequestLog\Pages;

use App\Models\User;
use App\MoonShine\Resources\AppUser\AppUserResource;
use App\MoonShine\Resources\RequestLog\RequestLogResource;
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
            Number::make('Status', 'status_code')
                ->sortable()
                ->badge(static fn (mixed $value, Field $field): string => self::statusBadgeColor((int) $value)),
            Number::make('Response ms', 'response_time')
                ->sortable()
                ->badge(static fn (mixed $value, Field $field): string => self::responseTimeBadgeColor((float) $value)),
            Text::make('Module', 'module_key')->sortable(),
            Text::make('Action', 'action_key')->sortable(),
            Text::make('Query', 'query_preview'),
            Text::make('Path', 'path'),
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

    protected function queryTags(): array
    {
        return [
            QueryTag::make('All', static fn (Builder $query): Builder => $query)->default()->icon('list-bullet'),
            QueryTag::make('Errors 4xx', static fn (Builder $query): Builder => $query->whereBetween('status_code', [400, 499]))->icon('exclamation-triangle'),
            QueryTag::make('Errors 5xx', static fn (Builder $query): Builder => $query->whereBetween('status_code', [500, 599]))->icon('x-circle'),
            QueryTag::make('Slow > 1500ms', static fn (Builder $query): Builder => $query->where('response_time', '>', 1500))->icon('clock'),
            QueryTag::make('Today', static fn (Builder $query): Builder => $query->whereDate('created_at', now()->toDateString()))->icon('calendar-days'),
        ];
    }

    private static function statusBadgeColor(int $statusCode): string
    {
        if ($statusCode >= 500) {
            return 'error';
        }

        if ($statusCode >= 400) {
            return 'warning';
        }

        if ($statusCode >= 200 && $statusCode < 400) {
            return 'success';
        }

        return 'gray';
    }

    private static function responseTimeBadgeColor(float $responseTime): string
    {
        if ($responseTime >= 3000) {
            return 'error';
        }

        if ($responseTime >= 1500) {
            return 'warning';
        }

        if ($responseTime > 0) {
            return 'success';
        }

        return 'gray';
    }
}
