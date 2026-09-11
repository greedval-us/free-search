<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\TelegramBotLink\Pages;

use App\Modules\TelegramBot\Application\BotAccess;
use App\Modules\TelegramBot\Models\BotLink;
use App\MoonShine\Resources\Shared\Pages\AdminIndexPage;
use App\MoonShine\Resources\TelegramBotLink\TelegramBotLinkResource;
use Illuminate\Database\Eloquent\Builder;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;

/** @extends AdminIndexPage<TelegramBotLinkResource> */
final class TelegramBotLinkIndexPage extends AdminIndexPage
{
    protected function fields(): iterable
    {
        $access = app(BotAccess::class);

        return [
            ID::make()->sortable(),
            Text::make(__('admin_panel.fields.user'), 'user_id', static fn (BotLink $link): string => $link->user?->email ?? '-'),
            Text::make(__('admin_panel.fields.telegram_id'), 'telegram_id'),
            Text::make(__('admin_bot.locale'), 'locale')->sortable(),
            Text::make(__('admin_bot.access'), 'access', static fn (BotLink $link): string => __('admin_bot.'.($access->allows($link) ? 'allowed' : 'restricted'))),
            ...array_map(
                static fn (string $field): Text => Text::make(
                    __('admin_bot.'.$field),
                    $field,
                    static fn (BotLink $link): string => __('admin_panel.values.'.($link->{$field} ? 'yes' : 'no')),
                ),
                ['notifications_enabled', 'exports_enabled', 'broadcasts_enabled'],
            ),
            Date::make(__('admin_bot.linked_at'), 'created_at')->format($this->adminDateTimeFormat())->sortable(),
        ];
    }

    protected function filters(): iterable
    {
        return [
            Text::make(__('admin_panel.fields.user_id'), 'user_id'),
            Select::make(__('admin_bot.locale'), 'locale')->options(['ru' => 'RU', 'en' => 'EN'])->nullable(),
            ...array_map(
                static fn (string $field): Select => Select::make(__('admin_bot.'.$field), $field)
                    ->options([1 => __('admin_panel.values.yes'), 0 => __('admin_panel.values.no')])->nullable(),
                ['notifications_enabled', 'exports_enabled', 'broadcasts_enabled'],
            ),
        ];
    }

    protected function queryTags(): array
    {
        return [
            $this->allTag(static fn (Builder $query): Builder => $query),
            $this->todayTag('created_at'),
        ];
    }
}
