<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\SubscriptionActivationToken;

use App\Models\SubscriptionActivationToken;
use App\MoonShine\Resources\SubscriptionActivationToken\Pages\SubscriptionActivationTokenFormPage;
use App\MoonShine\Resources\SubscriptionActivationToken\Pages\SubscriptionActivationTokenIndexPage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;

/**
 * @extends ModelResource<SubscriptionActivationToken, SubscriptionActivationTokenIndexPage, SubscriptionActivationTokenFormPage, null>
 */
#[Icon('ticket')]
#[Order(6)]
final class SubscriptionActivationTokenResource extends ModelResource
{
    protected string $model = SubscriptionActivationToken::class;

    protected string $column = 'token';

    protected bool $simplePaginate = true;

    public function getTitle(): string
    {
        return __('admin_panel.resources.subscription_activation_tokens');
    }

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->except(
            Action::VIEW,
            Action::MASS_DELETE,
        );
    }

    protected function pages(): array
    {
        return [
            SubscriptionActivationTokenIndexPage::class,
            SubscriptionActivationTokenFormPage::class,
        ];
    }

    protected function search(): array
    {
        return [
            'id',
            'token',
            'plan',
            'note',
            'usedBy.email',
        ];
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        $builder->with(['usedBy']);

        if (! $this->hasQueryParam('sort')) {
            $builder->orderByDesc('created_at');
        }

        return $builder;
    }
}
