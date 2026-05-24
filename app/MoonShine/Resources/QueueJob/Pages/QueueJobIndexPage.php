<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\QueueJob\Pages;

use App\Models\QueueJob;
use App\MoonShine\Resources\QueueJob\QueueJobResource;
use App\MoonShine\Support\Concerns\ParsesQueuePayload;
use Illuminate\Database\Eloquent\Builder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;

/**
 * @extends IndexPage<QueueJobResource>
 */
final class QueueJobIndexPage extends IndexPage
{
    use ParsesQueuePayload;

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Queue', 'queue')->sortable(),
            Number::make('Attempts', 'attempts')->sortable(),
            Text::make('Created At', 'created_at', static fn (QueueJob $job): string => self::formatUnixTimestamp($job->created_at)),
            Text::make('Available At', 'available_at', static fn (QueueJob $job): string => self::formatUnixTimestamp($job->available_at)),
            Text::make('Reserved At', 'reserved_at', static fn (QueueJob $job): string => self::formatUnixTimestamp($job->reserved_at)),
            Text::make('Job', 'payload', static fn (QueueJob $job): string => self::resolveJobDisplayName($job->payload)),
        ];
    }

    protected function filters(): iterable
    {
        return [
            Text::make('Queue', 'queue'),
            Number::make('Attempts', 'attempts'),
        ];
    }

    protected function queryTags(): array
    {
        return [
            QueryTag::make('All', static fn (Builder $query): Builder => $query)->default()->icon('list-bullet'),
            QueryTag::make('Retrying', static fn (Builder $query): Builder => $query->where('attempts', '>', 0))->icon('arrow-path'),
            QueryTag::make('Ready now', static fn (Builder $query): Builder => $query->where('available_at', '<=', time()))->icon('clock'),
        ];
    }

    private static function formatUnixTimestamp(int|null $value): string
    {
        if ($value === null || $value <= 0) {
            return '-';
        }

        return date('d.m.Y H:i:s', $value);
    }
}
