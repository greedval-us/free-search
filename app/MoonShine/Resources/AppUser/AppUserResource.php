<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\AppUser;

use App\Models\AdminAuditLog;
use App\Models\User;
use App\Models\UserSubscription;
use App\MoonShine\Resources\AppUser\Pages\AppUserFormPage;
use App\MoonShine\Resources\AppUser\Pages\AppUserIndexPage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\MenuManager\Attributes\Group;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;

/**
 * @extends ModelResource<User, AppUserIndexPage, AppUserFormPage, null>
 */
#[Icon('users')]
#[Group('moonshine::ui.resource.system', 'users', translatable: true)]
#[Order(10)]
class AppUserResource extends ModelResource
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $beforeUpdateSnapshots = [];

    /**
     * @var array<int, string>
     */
    private const AUDIT_FIELDS = [
        'name',
        'email',
        'account_type',
        'subscription_plan',
        'subscription_ends_at',
        'telegram_id',
        'is_blocked',
    ];

    protected string $model = User::class;

    protected string $column = 'email';

    protected bool $simplePaginate = true;

    public function getTitle(): string
    {
        return __('admin_panel.resources.registered_users');
    }

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->except(
            Action::CREATE,
            Action::VIEW,
            Action::DELETE,
            Action::MASS_DELETE,
        );
    }

    protected function pages(): array
    {
        return [
            AppUserIndexPage::class,
            AppUserFormPage::class,
        ];
    }

    protected function search(): array
    {
        return [
            'id',
            'name',
            'email',
            'account_type',
            'telegram_id',
            'is_blocked',
        ];
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        $builder->with(['activeSubscription'])->withCount('requestLogs');

        if (! $this->hasQueryParam('sort')) {
            $builder->orderByDesc('created_at');
        }

        return $builder;
    }

    protected function beforeUpdating(DataWrapperContract $item): DataWrapperContract
    {
        $model = $item->getOriginal();
        if ($model instanceof User && $model->id !== null) {
            $this->beforeUpdateSnapshots[(int) $model->id] = $this->snapshotUser($model);
        }

        return $item;
    }

    protected function afterUpdated(DataWrapperContract $item): DataWrapperContract
    {
        $model = $item->getOriginal();
        if (! $model instanceof User || $model->id === null) {
            return $item;
        }

        $before = $this->beforeUpdateSnapshots[(int) $model->id] ?? null;
        unset($this->beforeUpdateSnapshots[(int) $model->id]);
        if ($before === null) {
            return $item;
        }

        $this->syncSubscriptionFromRequest($model);
        $model->unsetRelation('activeSubscription');

        $after = $this->snapshotUser($model);
        $changes = $this->buildDiff($before, $after);
        if ($changes === []) {
            return $item;
        }

        $adminUser = auth('moonshine')->user();

        AdminAuditLog::query()->create([
            'actor_admin_id' => $adminUser?->id,
            'actor_admin_name' => $adminUser?->name ?? $adminUser?->email,
            'target_type' => 'user',
            'target_id' => (int) $model->id,
            'action' => 'user.updated',
            'changes' => $changes,
            'meta' => [
                'source' => 'moonshine',
                'ip' => request()?->ip(),
            ],
            'created_at' => now(),
        ]);

        return $item;
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotUser(User $user): array
    {
        $snapshot = [];

        foreach (self::AUDIT_FIELDS as $field) {
            $value = match ($field) {
                'subscription_plan' => $user->currentPlan()->value,
                'subscription_ends_at' => $user->activeSubscription()->first()?->ends_at,
                default => $user->{$field} ?? null,
            };

            if ($value instanceof \DateTimeInterface) {
                $value = $value->format(DATE_ATOM);
            }

            $snapshot[$field] = $value;
        }

        return $snapshot;
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<string, array<string, mixed>>
     */
    private function buildDiff(array $before, array $after): array
    {
        $diff = [];

        foreach (self::AUDIT_FIELDS as $field) {
            $old = $before[$field] ?? null;
            $new = $after[$field] ?? null;

            if ($old === $new) {
                continue;
            }

            $diff[$field] = [
                'old' => $old,
                'new' => $new,
            ];
        }

        return $diff;
    }

    private function syncSubscriptionFromRequest(User $user): void
    {
        if (! request()->has('subscription_plan')) {
            return;
        }

        $plan = (string) request()->input('subscription_plan', User::SUBSCRIPTION_PLAN_FREE);
        if (! in_array($plan, [
            User::SUBSCRIPTION_PLAN_FREE,
            User::SUBSCRIPTION_PLAN_PLUS,
            User::SUBSCRIPTION_PLAN_PRO,
        ], true)) {
            return;
        }

        $now = now();

        if ($plan === User::SUBSCRIPTION_PLAN_FREE) {
            $user->subscriptions()
                ->where('status', UserSubscription::STATUS_ACTIVE)
                ->where('ends_at', '>', $now)
                ->update([
                    'status' => UserSubscription::STATUS_CANCELED,
                    'ends_at' => $now,
                    'updated_at' => $now,
                ]);

            return;
        }

        $activeSubscription = $user->activeSubscription()->first();
        if ($activeSubscription instanceof UserSubscription && $activeSubscription->plan === $plan) {
            return;
        }

        $user->subscriptions()
            ->where('status', UserSubscription::STATUS_ACTIVE)
            ->where('ends_at', '>', $now)
            ->update([
                'status' => UserSubscription::STATUS_CANCELED,
                'ends_at' => $now,
                'updated_at' => $now,
            ]);

        $user->subscriptions()->create([
            'plan' => $plan,
            'status' => UserSubscription::STATUS_ACTIVE,
            'starts_at' => $now,
            'ends_at' => $now->copy()->addMonth(),
            'metadata' => [
                'source' => 'moonshine',
                'actor_admin_id' => auth('moonshine')->id(),
            ],
        ]);
    }
}
