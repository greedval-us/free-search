<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\FailedJob\Pages;

use App\Models\FailedJob;
use App\MoonShine\Resources\FailedJob\FailedJobResource;
use App\MoonShine\Resources\Shared\Pages\AdminIndexPage;
use App\MoonShine\Support\Concerns\ParsesQueuePayload;
use App\MoonShine\Support\Formatting\AdminPanelDateFormatter;
use Illuminate\Database\Eloquent\Builder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/**
 * @extends AdminIndexPage<FailedJobResource>
 */
final class FailedJobIndexPage extends AdminIndexPage
{
    use ParsesQueuePayload;

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Date::make(__('admin_panel.fields.failed_at'), 'failed_at')
                ->format(AdminPanelDateFormatter::DATE_TIME_FORMAT)
                ->sortable(),
            Text::make(__('admin_panel.fields.queue'), 'queue')->sortable(),
            Text::make(__('admin_panel.fields.connection'), 'connection')->sortable(),
            Text::make(__('admin_panel.fields.job'), 'payload', static fn (FailedJob $job): string => self::resolveJobDisplayName($job->payload)),
            Text::make(__('admin_panel.fields.error'), 'exception', static fn (FailedJob $job): string => self::resolveExceptionSummary($job->exception)),
        ];
    }

    protected function filters(): iterable
    {
        return [
            Text::make(__('admin_panel.fields.queue'), 'queue'),
            Text::make(__('admin_panel.fields.connection'), 'connection'),
            Text::make(__('admin_panel.fields.uuid'), 'uuid'),
        ];
    }

    protected function queryTags(): array
    {
        return [
            $this->allTag(static fn (Builder $query): Builder => $query),
            $this->todayTag('failed_at'),
            $this->last24HoursTag('failed_at'),
        ];
    }

}
