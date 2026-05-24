<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\AppUser\Pages;

use App\Models\User;
use App\MoonShine\Resources\AppUser\AppUserResource;
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
            Text::make('Name', 'name')->sortable(),
            Email::make('E-mail', 'email')->sortable(),
            Text::make('Type', 'account_type')->sortable(),
            Text::make('Telegram ID', 'telegram_id'),
            Number::make('Requests', 'request_logs_count')->sortable(),
            Date::make('Created At', 'created_at')
                ->format('d.m.Y H:i')
                ->sortable(),
        ];
    }

    protected function filters(): iterable
    {
        return [
            Text::make('Name', 'name'),
            Email::make('E-mail', 'email'),
            Select::make('Type', 'account_type')->options([
                User::ACCOUNT_TYPE_USER => 'user',
                User::ACCOUNT_TYPE_ADMIN => 'admin',
                User::ACCOUNT_TYPE_MODERATOR => 'moderator',
            ]),
        ];
    }
}
