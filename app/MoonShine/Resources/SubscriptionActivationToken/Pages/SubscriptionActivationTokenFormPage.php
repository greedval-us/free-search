<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\SubscriptionActivationToken\Pages;

use App\Models\SubscriptionActivationToken;
use App\MoonShine\Resources\SubscriptionActivationToken\SubscriptionActivationTokenResource;
use App\MoonShine\Resources\UserSubscription\Pages\UserSubscriptionIndexPage;
use Illuminate\Validation\Rule;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;

/**
 * @extends FormPage<SubscriptionActivationTokenResource, SubscriptionActivationToken>
 */
final class SubscriptionActivationTokenFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make(),
                Preview::make(
                    __('admin_panel.fields.activation_token'),
                    'token',
                    static fn (mixed $original): string => $original instanceof SubscriptionActivationToken
                        ? ((string) ($original->token ?: __('admin_panel.values.generated_after_save')))
                        : __('admin_panel.values.generated_after_save')
                )->onApply(static fn (mixed $data, mixed $value, mixed $field): mixed => $data),
                Select::make(__('admin_panel.fields.plan'), 'plan')
                    ->options(SubscriptionActivationTokenIndexPage::tokenPlanOptions())
                    ->required(),
                Text::make(__('admin_panel.fields.note'), 'note')->nullable(),
            ]),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            'plan' => ['required', Rule::in(array_keys(SubscriptionActivationTokenIndexPage::tokenPlanOptions()))],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
