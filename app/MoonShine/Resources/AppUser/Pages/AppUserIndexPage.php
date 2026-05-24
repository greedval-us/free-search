<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\AppUser\Pages;

use App\Models\User;
use App\MoonShine\Resources\AppUser\AppUserResource;
use MoonShine\UI\Fields\Field;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Email;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;

/**
 * @extends IndexPage<AppUserResource>
 */
final class AppUserIndexPage extends IndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make(__('admin_panel.fields.name'), 'name')->sortable(),
            Email::make(__('admin_panel.fields.email'), 'email')->sortable(),
            Text::make(__('admin_panel.fields.account_type'), 'account_type')->sortable(),
            Text::make(__('admin_panel.fields.premium'), 'is_premium', static fn (mixed $original): string => self::resolveFlag($original, 'is_premium') ? __('admin_panel.values.yes') : __('admin_panel.values.no'))
                ->badge(static fn (mixed $value, Field $field): string => self::resolveFlag($value, 'is_premium') ? 'success' : 'gray'),
            Text::make(__('admin_panel.fields.blocked'), 'is_blocked', static fn (mixed $original): string => self::resolveFlag($original, 'is_blocked') ? __('admin_panel.values.blocked') : __('admin_panel.values.active'))
                ->badge(static fn (mixed $value, Field $field): string => self::resolveFlag($value, 'is_blocked') ? 'error' : 'success'),
            Text::make(__('admin_panel.fields.telegram_id'), 'telegram_id'),
            Number::make(__('admin_panel.fields.requests'), 'request_logs_count')->sortable(),
            Date::make(__('admin_panel.fields.created_at'), 'created_at')
                ->format('d.m.Y H:i')
                ->sortable(),
        ];
    }

    protected function filters(): iterable
    {
        return [
            Text::make(__('admin_panel.fields.name'), 'name'),
            Email::make(__('admin_panel.fields.email'), 'email'),
            Select::make(__('admin_panel.fields.account_type'), 'account_type')->options([
                User::ACCOUNT_TYPE_USER => __('admin_panel.values.user'),
                User::ACCOUNT_TYPE_ADMIN => __('admin_panel.values.admin'),
                User::ACCOUNT_TYPE_MODERATOR => __('admin_panel.values.moderator'),
            ]),
            Select::make(__('admin_panel.fields.premium'), 'is_premium')->options([
                1 => __('admin_panel.values.yes'),
                0 => __('admin_panel.values.no'),
            ]),
            Select::make(__('admin_panel.fields.blocked'), 'is_blocked')->options([
                1 => __('admin_panel.values.blocked'),
                0 => __('admin_panel.values.active'),
            ]),
        ];
    }

    private static function resolveFlag(mixed $value, string $field): bool
    {
        if ($value instanceof User) {
            return (bool) ($value->{$field} ?? false);
        }

        if (is_array($value)) {
            return (bool) ($value[$field] ?? false);
        }

        return (bool) $value;
    }
}
