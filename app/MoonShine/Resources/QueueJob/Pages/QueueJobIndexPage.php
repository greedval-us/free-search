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
            Text::make(__('admin_panel.fields.queue'), 'queue')->sortable(),
            Number::make(__('admin_panel.fields.attempts'), 'attempts')->sortable(),
            Text::make(__('admin_panel.fields.created_at'), 'created_at', static fn (QueueJob $job): string => self::formatUnixTimestamp($job->created_at)),
            Text::make(__('admin_panel.fields.available_at'), 'available_at', static fn (QueueJob $job): string => self::formatUnixTimestamp($job->available_at)),
            Text::make(__('admin_panel.fields.reserved_at'), 'reserved_at', static fn (QueueJob $job): string => self::formatUnixTimestamp($job->reserved_at)),
            Text::make(__('admin_panel.fields.job'), 'payload', static fn (QueueJob $job): string => self::resolveJobDisplayName($job->payload)),
        ];
    }

    protected function filters(): iterable
    {
        return [
            Text::make(__('admin_panel.fields.queue'), 'queue'),
            Number::make(__('admin_panel.fields.attempts'), 'attempts'),
        ];
    }

    protected function queryTags(): array
    {
        return [
            QueryTag::make(__('admin_panel.tags.all'), static fn (Builder $query): Builder => $query)->default()->icon('list-bullet'),
            QueryTag::make(__('admin_panel.tags.retrying'), static fn (Builder $query): Builder => $query->where('attempts', '>', 0))->icon('arrow-path'),
            QueryTag::make(__('admin_panel.tags.ready_now'), static fn (Builder $query): Builder => $query->where('available_at', '<=', time()))->icon('clock'),
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
