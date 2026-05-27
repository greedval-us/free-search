<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\AppUser\Pages;

use App\Models\User;
use App\MoonShine\Resources\AppUser\AppUserResource;
use App\MoonShine\Resources\UserSubscription\Pages\UserSubscriptionIndexPage;
use App\MoonShine\Support\UserQuotaSummaryFormatter;
use Illuminate\Validation\Rule;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Flex;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Email;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Preview;
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
                    Text::make(__('admin_panel.fields.name'), 'name')
                        ->disabled()
                        ->onApply(static fn (mixed $data, mixed $value, mixed $field): mixed => $data),
                    Email::make(__('admin_panel.fields.email'), 'email')
                        ->disabled()
                        ->onApply(static fn (mixed $data, mixed $value, mixed $field): mixed => $data),
                ]),

                Select::make(__('admin_panel.fields.account_type'), 'account_type')
                    ->disabled()
                    ->onApply(static fn (mixed $data, mixed $value, mixed $field): mixed => $data)
                    ->options([
                        User::ACCOUNT_TYPE_USER => __('admin_panel.values.user'),
                        User::ACCOUNT_TYPE_ADMIN => __('admin_panel.values.admin'),
                        User::ACCOUNT_TYPE_MODERATOR => __('admin_panel.values.moderator'),
                    ]),

                Text::make(__('admin_panel.fields.telegram_id'), 'telegram_id')->nullable(),

                Select::make(__('admin_panel.fields.plan'), 'subscription_plan', static fn (mixed $original): string => $original instanceof User
                    ? $original->currentPlan()->value
                    : User::SUBSCRIPTION_PLAN_FREE)
                    ->options(UserSubscriptionIndexPage::planOptions())
                    ->onApply(static fn (mixed $data, mixed $value, mixed $field): mixed => $data),

                Date::make(__('admin_panel.fields.subscription_ends_at'), 'activeSubscription.ends_at')
                    ->format('d.m.Y H:i')
                    ->disabled()
                    ->onApply(static fn (mixed $data, mixed $value, mixed $field): mixed => $data),

                Preview::make(__('admin_panel.fields.quota_remaining'), 'id', static fn (mixed $original): mixed => $original instanceof User
                    ? app(UserQuotaSummaryFormatter::class)->forDetails($original)
                    : '-')
                    ->onApply(static fn (mixed $data, mixed $value, mixed $field): mixed => $data),

                Switcher::make(__('admin_panel.fields.blocked'), 'is_blocked'),
            ]),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            'name' => ['sometimes', 'prohibited'],
            'email' => ['sometimes', 'prohibited'],
            'account_type' => ['sometimes', 'prohibited'],
            'subscription_plan' => ['nullable', Rule::in(array_keys(UserSubscriptionIndexPage::planOptions()))],
            'telegram_id' => ['nullable', 'string', 'max:255'],
            'is_blocked' => ['nullable', 'boolean'],
        ];
    }
}
