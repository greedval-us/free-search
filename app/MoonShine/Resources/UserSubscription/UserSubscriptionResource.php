<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\UserSubscription;

use App\Models\UserSubscription;
use App\MoonShine\Resources\UserSubscription\Pages\UserSubscriptionFormPage;
use App\MoonShine\Resources\UserSubscription\Pages\UserSubscriptionIndexPage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;

/**
 * @extends ModelResource<UserSubscription, UserSubscriptionIndexPage, UserSubscriptionFormPage, null>
 */
#[Icon('credit-card')]
#[Order(5)]
class UserSubscriptionResource extends ModelResource
{
    protected string $model = UserSubscription::class;

    protected string $column = 'plan';

    protected bool $simplePaginate = true;

    public function getTitle(): string
    {
        return __('admin_panel.resources.user_subscriptions');
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
            UserSubscriptionIndexPage::class,
            UserSubscriptionFormPage::class,
        ];
    }

    protected function search(): array
    {
        return [
            'id',
            'plan',
            'status',
            'user.email',
        ];
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        $builder->with(['user']);

        if (! $this->hasQueryParam('sort')) {
            $builder->orderByDesc('ends_at');
        }

        return $builder;
    }
}
