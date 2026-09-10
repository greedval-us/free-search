<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\TelegramBotDelivery\Pages;

use App\Modules\TelegramBot\Models\BotDelivery;
use App\MoonShine\Resources\Shared\Pages\AdminIndexPage;
use App\MoonShine\Resources\TelegramBotDelivery\TelegramBotDeliveryResource;
use App\MoonShine\Support\Formatting\TelegramBotFormatter;
use Illuminate\Database\Eloquent\Builder;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;

/** @extends AdminIndexPage<TelegramBotDeliveryResource> */
final class TelegramBotDeliveryIndexPage extends AdminIndexPage
{
    protected function fields(): iterable
    {
        $formatter = new TelegramBotFormatter;

        return [
            ID::make()->sortable(),
            Text::make(__('admin_bot.link_id'), 'link_id')->sortable(),
            Text::make(__('admin_bot.kind'), 'kind', static fn (BotDelivery $delivery): string => $formatter->kind($delivery->kind)),
            Text::make(__('admin_bot.mode'), 'automatic', static fn (BotDelivery $delivery): string => __('admin_bot.'.($delivery->automatic ? 'automatic' : 'manual'))),
            Text::make(__('admin_panel.fields.status'), 'status', static fn (BotDelivery $delivery): string => $formatter->status($delivery->status))
                ->badge(static fn (mixed $value): string => $formatter->statusColor((string) $value))->sortable(),
            Date::make(__('admin_panel.fields.created_at'), 'created_at')->format($this->adminDateTimeFormat())->sortable(),
            Date::make(__('admin_bot.dispatched_at'), 'dispatched_at')->format($this->adminDateTimeFormat()),
            Date::make(__('admin_bot.sent_at'), 'sent_at')->format($this->adminDateTimeFormat())->sortable(),
            Text::make(__('admin_panel.fields.error'), 'error_code', static fn (BotDelivery $delivery): string => $formatter->error($delivery->error_code)),
        ];
    }

    protected function filters(): iterable
    {
        $formatter = new TelegramBotFormatter;

        return [
            Select::make(__('admin_panel.fields.status'), 'status')->options($formatter->statuses())->nullable(),
            Select::make(__('admin_bot.kind'), 'kind')->options($formatter->kinds())->nullable(),
            Select::make(__('admin_bot.mode'), 'automatic')->options([1 => __('admin_bot.automatic'), 0 => __('admin_bot.manual')])->nullable(),
            Text::make(__('admin_bot.link_id'), 'link_id'),
        ];
    }

    protected function queryTags(): array
    {
        return [
            $this->allTag(static fn (Builder $query): Builder => $query),
            $this->customTag(__('admin_bot.statuses.failed'), static fn (Builder $query): Builder => $query->where('status', BotDelivery::FAILED), 'exclamation-circle'),
            $this->customTag(__('admin_bot.statuses.pending'), static fn (Builder $query): Builder => $query->where('status', BotDelivery::PENDING), 'clock'),
            $this->todayTag('created_at'),
        ];
    }
}
