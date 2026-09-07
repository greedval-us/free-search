<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\ParserRun\Pages;

use App\Models\ParserRun;
use App\Modules\ParserSupport\Enums\ParserRunStatus;
use App\MoonShine\Resources\ParserRun\ParserRunResource;
use App\MoonShine\Resources\Shared\Pages\AdminIndexPage;
use App\MoonShine\Support\Formatting\AdminPanelDateFormatter;
use App\MoonShine\Support\Formatting\ParserRunFormatter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Field;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;

/**
 * @extends AdminIndexPage<ParserRunResource>
 */
final class ParserRunIndexPage extends AdminIndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        $formatter = new ParserRunFormatter;

        return [
            ID::make()->sortable(),
            Text::make(
                __('admin_panel.fields.run_id'),
                'run_id',
                static fn (ParserRun $run): string => Str::limit($run->run_id, 12, ''),
            ),
            Date::make(__('admin_panel.fields.started_at'), 'started_at')
                ->format(AdminPanelDateFormatter::DATE_TIME_FORMAT)
                ->sortable(),
            Text::make(
                __('admin_panel.fields.user'),
                'user_id',
                static fn (ParserRun $run): string => $run->user?->email ?? '-',
            ),
            Text::make(
                __('admin_panel.fields.module'),
                'module',
                static fn (ParserRun $run): string => $formatter->moduleLabel($run->module),
            )->sortable(),
            Text::make(
                __('admin_panel.fields.status'),
                'status',
                static fn (ParserRun $run): string => $formatter->statusLabel($run->status),
            )
                ->sortable()
                ->badge(static fn (mixed $value, Field $field): string => $formatter->statusColor((string) $value)),
            Text::make(
                __('admin_panel.fields.progress'),
                'progress',
                static fn (ParserRun $run): string => ParserRun::normalizeProgress($run->progress).'%',
            )->sortable(),
            Text::make(__('admin_panel.fields.stage'), 'stage'),
            Text::make(
                __('admin_panel.fields.file_size'),
                'file_size_bytes',
                static fn (ParserRun $run): string => $formatter->fileSize($run->file_size_bytes),
            ),
            Date::make(__('admin_panel.fields.last_activity_at'), 'last_activity_at')
                ->format(AdminPanelDateFormatter::DATE_TIME_FORMAT)
                ->sortable(),
            Text::make(
                __('admin_panel.fields.error'),
                'error',
                static fn (ParserRun $run): string => $formatter->errorSummary($run->error),
            ),
        ];
    }

    protected function filters(): iterable
    {
        return [
            Select::make(__('admin_panel.fields.module'), 'module')->options($this->moduleOptions()),
            Select::make(__('admin_panel.fields.status'), 'status')->options($this->statusOptions()),
            Text::make(__('admin_panel.fields.user_id'), 'user_id'),
        ];
    }

    protected function queryTags(): array
    {
        return [
            $this->allTag(static fn (Builder $query): Builder => $query),
            $this->customTag(
                __('admin_panel.tags.parser_running'),
                static fn (Builder $query): Builder => $query->where('status', ParserRunStatus::Running->value),
                'play-circle',
                true,
            ),
            $this->customTag(
                __('admin_panel.tags.parser_failed'),
                static fn (Builder $query): Builder => $query->where('status', ParserRunStatus::Failed->value),
                'x-circle',
            ),
            $this->customTag(
                __('admin_panel.tags.parser_completed_today'),
                static fn (Builder $query): Builder => $query
                    ->where('status', ParserRunStatus::Completed->value)
                    ->whereDate('finished_at', today()),
                'check-circle',
            ),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function moduleOptions(): array
    {
        $formatter = new ParserRunFormatter;
        $modules = ['telegram', 'youtube', 'mastodon', 'bluesky'];

        return array_combine(
            $modules,
            array_map($formatter->moduleLabel(...), $modules),
        );
    }

    /**
     * @return array<string, string>
     */
    private function statusOptions(): array
    {
        $formatter = new ParserRunFormatter;

        return collect(ParserRunStatus::cases())
            ->mapWithKeys(static fn (ParserRunStatus $status): array => [
                $status->value => $formatter->statusLabel($status->value),
            ])
            ->all();
    }
}
