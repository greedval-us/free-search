<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\SubscriptionActivationToken\Pages;

use App\Models\User;
use App\MoonShine\Resources\AppUser\AppUserResource;
use App\MoonShine\Resources\Shared\Pages\AdminIndexPage;
use App\MoonShine\Resources\SubscriptionActivationToken\SubscriptionActivationTokenResource;
use App\MoonShine\Resources\UserSubscription\Pages\UserSubscriptionIndexPage;
use Illuminate\Database\Eloquent\Builder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Field;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;

/**
 * @extends AdminIndexPage<SubscriptionActivationTokenResource>
 */
final class SubscriptionActivationTokenIndexPage extends AdminIndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make(__('admin_panel.fields.activation_token'), 'token')->sortable(),
            Text::make(__('admin_panel.fields.plan'), 'plan')
                ->sortable()
                ->badge(static fn (mixed $value, Field $field): string => self::planBadge((string) $value)),
            Date::make(__('admin_panel.fields.expires_at'), 'expires_at')
                ->format('d.m.Y H:i')
                ->sortable(),
            BelongsTo::make(
                __('admin_panel.fields.used_by'),
                'usedBy',
                formatted: static fn (?User $model): string => $model?->email ?? __('admin_panel.values.not_available'),
                resource: AppUserResource::class,
            ),
            Date::make(__('admin_panel.fields.used_at'), 'used_at')
                ->format('d.m.Y H:i')
                ->sortable(),
            Text::make(__('admin_panel.fields.note'), 'note'),
            Date::make(__('admin_panel.fields.created_at'), 'created_at')
                ->format('d.m.Y H:i')
                ->sortable(),
        ];
    }

    protected function filters(): iterable
    {
        return [
            Select::make(__('admin_panel.fields.plan'), 'plan')->options(self::tokenPlanOptions()),
            Select::make(__('admin_panel.fields.token_status'), 'token_status')->options([
                'available' => __('admin_panel.values.available'),
                'used' => __('admin_panel.values.used'),
            ]),
        ];
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        $status = $this->getQueryParam('token_status');

        return match ($status) {
            'available' => $builder->whereNull('used_at'),
            'used' => $builder->whereNotNull('used_at'),
            default => $builder,
        };
    }

    protected function queryTags(): array
    {
        return [
            $this->allTag(static fn (Builder $query): Builder => $query),
            $this->customTag(
                __('admin_panel.tags.available_tokens'),
                static fn (Builder $query): Builder => $query->whereNull('used_at'),
                'ticket',
                true,
            ),
            $this->customTag(
                __('admin_panel.tags.used_tokens'),
                static fn (Builder $query): Builder => $query->whereNotNull('used_at'),
                'check-circle',
            ),
        ];
    }

    private static function planBadge(string $plan): string
    {
        return match ($plan) {
            User::SUBSCRIPTION_PLAN_PRO => 'success',
            User::SUBSCRIPTION_PLAN_PLUS => 'warning',
            default => 'gray',
        };
    }

    public static function tokenPlanOptions(): array
    {
        return array_intersect_key(
            UserSubscriptionIndexPage::planOptions(),
            array_flip([
                User::SUBSCRIPTION_PLAN_PLUS,
                User::SUBSCRIPTION_PLAN_PRO,
            ])
        );
    }
}
