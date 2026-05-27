<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\UserSubscription\Pages;

use App\Models\User;
use App\Models\UserSubscription;
use App\MoonShine\Resources\AppUser\AppUserResource;
use App\MoonShine\Resources\UserSubscription\UserSubscriptionResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;

/**
 * @extends FormPage<UserSubscriptionResource, UserSubscription>
 */
final class UserSubscriptionFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make(),
                BelongsTo::make(
                    __('admin_panel.fields.user'),
                    'user',
                    formatted: static fn (?User $model): string => $model?->email ?? '-',
                    resource: AppUserResource::class,
                )->valuesQuery(static fn (Builder $q): Builder => $q->select(['id', 'email'])),
                Select::make(__('admin_panel.fields.plan'), 'plan')->options(UserSubscriptionIndexPage::planOptions()),
                Select::make(__('admin_panel.fields.status'), 'status')->options(UserSubscriptionIndexPage::statusOptions()),
                Date::make(__('admin_panel.fields.starts_at'), 'starts_at')
                    ->withTime()
                    ->required(),
                Date::make(__('admin_panel.fields.ends_at'), 'ends_at')
                    ->withTime()
                    ->required(),
                Json::make(__('admin_panel.fields.metadata'), 'metadata')
                    ->keyValue('key', 'value', valueField: Text::make('value', 'value'))
                    ->nullable(),
            ]),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'plan' => ['required', Rule::in(array_keys(UserSubscriptionIndexPage::planOptions()))],
            'status' => ['required', Rule::in(array_keys(UserSubscriptionIndexPage::statusOptions()))],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
