<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\AppUser\Pages;

use App\Models\User;
use App\MoonShine\Resources\AppUser\AppUserResource;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Flex;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Email;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;

/**
 * @extends FormPage<AppUserResource, User>
 */
final class AppUserFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make(),

                Flex::make([
                    Text::make('Name', 'name')
                        ->required(),
                    Email::make('E-mail', 'email')
                        ->required(),
                ]),

                Select::make('Account type', 'account_type')
                    ->required()
                    ->options([
                        User::ACCOUNT_TYPE_USER => 'user',
                        User::ACCOUNT_TYPE_ADMIN => 'admin',
                        User::ACCOUNT_TYPE_MODERATOR => 'moderator',
                    ]),

                Text::make('Telegram ID', 'telegram_id')->nullable(),

                Switcher::make('Premium enabled', 'is_premium'),
                Date::make('Premium expires at', 'premium_expires_at')
                    ->withTime()
                    ->nullable(),

                Switcher::make('Blocked', 'is_blocked'),
            ]),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'account_type' => ['required', 'in:user,admin,moderator'],
            'telegram_id' => ['nullable', 'string', 'max:255'],
            'is_premium' => ['nullable', 'boolean'],
            'premium_expires_at' => ['nullable', 'date'],
            'is_blocked' => ['nullable', 'boolean'],
        ];
    }
}
