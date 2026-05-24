<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\FailedJob\Pages;

use App\Models\FailedJob;
use App\MoonShine\Resources\FailedJob\FailedJobResource;
use App\MoonShine\Support\Concerns\ParsesQueuePayload;
use Illuminate\Database\Eloquent\Builder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/**
 * @extends IndexPage<FailedJobResource>
 */
final class FailedJobIndexPage extends IndexPage
{
    use ParsesQueuePayload;

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Date::make('Failed At', 'failed_at')
                ->format('d.m.Y H:i:s')
                ->sortable(),
            Text::make('Queue', 'queue')->sortable(),
            Text::make('Connection', 'connection')->sortable(),
            Text::make('Job', 'payload', static fn (FailedJob $job): string => self::resolveJobDisplayName($job->payload)),
            Text::make('Error', 'exception', static fn (FailedJob $job): string => self::resolveExceptionSummary($job->exception)),
        ];
    }

    protected function filters(): iterable
    {
        return [
            Text::make('Queue', 'queue'),
            Text::make('Connection', 'connection'),
            Text::make('UUID', 'uuid'),
        ];
    }

    protected function queryTags(): array
    {
        return [
            QueryTag::make('All', static fn (Builder $query): Builder => $query)->default()->icon('list-bullet'),
            QueryTag::make('Today', static fn (Builder $query): Builder => $query->whereDate('failed_at', now()->toDateString()))->icon('calendar-days'),
            QueryTag::make('Last 24h', static fn (Builder $query): Builder => $query->where('failed_at', '>=', now()->subDay()))->icon('clock'),
        ];
    }

}
