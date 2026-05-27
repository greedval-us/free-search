<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\AppUser\Pages;

use App\Models\User;
use App\MoonShine\Resources\AppUser\AppUserResource;
use App\MoonShine\Resources\Shared\Pages\AdminIndexPage;
use App\MoonShine\Support\UserQuotaSummaryFormatter;
use Illuminate\Database\Eloquent\Builder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Email;
use MoonShine\UI\Fields\Field;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;

/**
 * @extends AdminIndexPage<AppUserResource>
 */
final class AppUserIndexPage extends AdminIndexPage
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
            Text::make(__('admin_panel.fields.plan'), 'id', static fn (mixed $original): string => self::resolvePlan($original))
                ->badge(static fn (mixed $value, Field $field): string => self::planBadge($value)),
            Date::make(__('admin_panel.fields.subscription_ends_at'), 'activeSubscription.ends_at')
                ->format('d.m.Y H:i'),
            Text::make(__('admin_panel.fields.quota_remaining'), 'id', static fn (mixed $original): string => self::quotaSummary($original)),
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
            Select::make(__('admin_panel.fields.blocked'), 'is_blocked')->options([
                1 => __('admin_panel.values.blocked'),
                0 => __('admin_panel.values.active'),
            ]),
        ];
    }

    protected function queryTags(): array
    {
        return [
            $this->allTag(static fn (Builder $query): Builder => $query),
            $this->customTag(
                __('admin_panel.tags.blocked_users'),
                static fn (Builder $query): Builder => $query->where('is_blocked', true),
                'lock-closed',
            ),
            $this->customTag(
                __('admin_panel.tags.paid_users'),
                static fn (Builder $query): Builder => $query->whereHas('activeSubscription'),
                'star',
            ),
            $this->customTag(
                __('admin_panel.tags.plus_users'),
                static fn (Builder $query): Builder => $query->whereHas(
                    'activeSubscription',
                    static fn (Builder $subscription): Builder => $subscription->where('plan', User::SUBSCRIPTION_PLAN_PLUS),
                ),
                'sparkles',
            ),
            $this->customTag(
                __('admin_panel.tags.pro_users'),
                static fn (Builder $query): Builder => $query->whereHas(
                    'activeSubscription',
                    static fn (Builder $subscription): Builder => $subscription->where('plan', User::SUBSCRIPTION_PLAN_PRO),
                ),
                'shield-check',
            ),
            $this->todayTag('created_at'),
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

    private static function resolvePlan(mixed $value): string
    {
        if (is_string($value) && \in_array($value, [
            User::SUBSCRIPTION_PLAN_FREE,
            User::SUBSCRIPTION_PLAN_PLUS,
            User::SUBSCRIPTION_PLAN_PRO,
        ], true)) {
            return $value;
        }

        if ($value instanceof User) {
            return $value->currentPlan()->value;
        }

        return User::SUBSCRIPTION_PLAN_FREE;
    }

    private static function planBadge(mixed $value): string
    {
        return match (self::resolvePlan($value)) {
            User::SUBSCRIPTION_PLAN_PRO => 'success',
            User::SUBSCRIPTION_PLAN_PLUS => 'warning',
            default => 'gray',
        };
    }

    private static function quotaSummary(mixed $value): string
    {
        if (! $value instanceof User) {
            return '-';
        }

        return app(UserQuotaSummaryFormatter::class)->format($value);
    }
}
