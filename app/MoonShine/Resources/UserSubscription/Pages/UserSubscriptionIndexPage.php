<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\UserSubscription\Pages;

use App\Models\User;
use App\Models\UserSubscription;
use App\MoonShine\Resources\AppUser\AppUserResource;
use App\MoonShine\Resources\Shared\Pages\AdminIndexPage;
use App\MoonShine\Resources\UserSubscription\UserSubscriptionResource;
use Illuminate\Database\Eloquent\Builder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Field;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;

/**
 * @extends AdminIndexPage<UserSubscriptionResource>
 */
final class UserSubscriptionIndexPage extends AdminIndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make(
                __('admin_panel.fields.user'),
                'user',
                formatted: static fn (?User $model): string => $model?->email ?? '-',
                resource: AppUserResource::class,
            ),
            Text::make(__('admin_panel.fields.plan'), 'plan')
                ->sortable()
                ->badge(static fn (mixed $value, Field $field): string => self::planBadge((string) $value)),
            Text::make(__('admin_panel.fields.status'), 'status')
                ->sortable()
                ->badge(static fn (mixed $value, Field $field): string => self::statusBadge((string) $value)),
            Date::make(__('admin_panel.fields.starts_at'), 'starts_at')
                ->format('d.m.Y H:i')
                ->sortable(),
            Date::make(__('admin_panel.fields.ends_at'), 'ends_at')
                ->format('d.m.Y H:i')
                ->sortable(),
            Date::make(__('admin_panel.fields.created_at'), 'created_at')
                ->format('d.m.Y H:i')
                ->sortable(),
        ];
    }

    protected function filters(): iterable
    {
        return [
            BelongsTo::make(
                __('admin_panel.fields.user'),
                'user',
                formatted: static fn (User $model): string => $model->email,
                resource: AppUserResource::class,
            )->valuesQuery(static fn (Builder $q): Builder => $q->select(['id', 'email'])),
            Select::make(__('admin_panel.fields.plan'), 'plan')->options(self::planOptions()),
            Select::make(__('admin_panel.fields.status'), 'status')->options(self::statusOptions()),
        ];
    }

    protected function queryTags(): array
    {
        return [
            $this->allTag(static fn (Builder $query): Builder => $query),
            $this->customTag(
                __('admin_panel.tags.active_subscriptions'),
                static fn (Builder $query): Builder => $query
                    ->where('status', UserSubscription::STATUS_ACTIVE)
                    ->where('starts_at', '<=', now())
                    ->where('ends_at', '>', now()),
                'check-circle',
                true,
            ),
            $this->customTag(
                __('admin_panel.tags.plus_users'),
                static fn (Builder $query): Builder => $query->where('plan', User::SUBSCRIPTION_PLAN_PLUS),
                'sparkles',
            ),
            $this->customTag(
                __('admin_panel.tags.pro_users'),
                static fn (Builder $query): Builder => $query->where('plan', User::SUBSCRIPTION_PLAN_PRO),
                'shield-check',
            ),
        ];
    }

    public static function planOptions(): array
    {
        return [
            User::SUBSCRIPTION_PLAN_FREE => 'free',
            User::SUBSCRIPTION_PLAN_PLUS => 'plus',
            User::SUBSCRIPTION_PLAN_PRO => 'pro',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            UserSubscription::STATUS_ACTIVE => __('admin_panel.values.active'),
            UserSubscription::STATUS_CANCELED => __('admin_panel.values.canceled'),
            UserSubscription::STATUS_EXPIRED => __('admin_panel.values.expired'),
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

    private static function statusBadge(string $status): string
    {
        return match ($status) {
            UserSubscription::STATUS_ACTIVE => 'success',
            UserSubscription::STATUS_CANCELED => 'warning',
            UserSubscription::STATUS_EXPIRED => 'gray',
            default => 'gray',
        };
    }
}
