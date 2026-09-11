<?php

namespace App\Modules\Telegram\Tracking;

use App\Models\TelegramTracking;
use App\Models\User;
use App\Modules\Telegram\Tracking\Contracts\TrackingGateway;
use App\Support\Access\Enums\AccountPlan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final readonly class TrackingService
{
    public function __construct(private TrackingConfig $config, private TrackingGateway $gateway,
        private TrackingLifecycle $lifecycle, private TrackingCapacity $capacity) {}

    public function create(User $user, array $data): TelegramTracking
    {
        $this->config->ensureQueue();
        // The definitive quota check runs under a row lock after network validation.
        $this->lifecycle->synchronize($user->id);
        $this->checkCapacity($user);
        $sources = $this->gateway->resolve($data['groups'], $data['mode'] === 'keyword' ? $data['query'] : null);

        return DB::transaction(function () use ($user, $data, $sources): TelegramTracking {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $this->lifecycle->synchronizeLocked($locked);
            $this->checkCapacity($locked);
            if ($this->openTasks($locked)->count() >= $this->config->integer('max_open_tasks')) {
                throw new TrackingException('open_limit');
            }
            $task = TelegramTracking::query()->create([
                'user_id' => $locked->id, 'name' => $data['name'], 'mode' => $data['mode'], 'query' => $data['query'],
                'notify_bot' => $data['notify_bot'] ?? false,
                ...$this->activationAttributes($locked),
                'started_at' => now(), 'expires_at' => now()->addMonthsNoOverflow($this->config->integer('duration_months')),
            ]);
            foreach ($sources as $source) {
                $task->sources()->create([...$source, 'collect_from' => now(),
                    'next_check_at' => now()->addHours($this->config->interval())]);
            }

            return $task;
        });
    }

    public function change(User $user, int $id, string $action, ?bool $notifyBot = null): void
    {
        DB::transaction(function () use ($user, $id, $action, $notifyBot): void {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $this->lifecycle->synchronizeLocked($locked);
            $task = TelegramTracking::query()->forUser($user->id)->lockForUpdate()->findOrFail($id);
            if ($task->ended_at !== null) {
                throw new TrackingException('finished');
            }
            match ($action) {
                'pause' => $this->lifecycle->pause($task),
                'stop' => $this->lifecycle->end($task),
                'resume' => $this->resume($locked, $task),
                'renew' => $this->renew($task),
                'preferences' => $task->update(['notify_bot' => (bool) $notifyBot]),
                default => throw new TrackingException('invalid_action'),
            };
        });
    }

    private function resume(User $user, TelegramTracking $task): void
    {
        if ($task->status === TelegramTracking::ACTIVE) {
            return;
        }
        $this->config->ensureQueue();
        $this->checkCapacity($user);
        $task->update([
            'status' => TelegramTracking::ACTIVE,
            'pause_reason' => null,
            ...$this->activationAttributes($user),
        ]);
        $task->sources()->update([
            'collect_from' => now(), 'window_start' => null, 'window_end' => null, 'collection_method' => null,
            'offset_id' => 0, 'high_id' => 0, 'lease_token' => null, 'lease_until' => null,
            'error_code' => null, 'failure_count' => 0,
            'next_check_at' => now()->addHours($this->config->interval()),
        ]);
    }

    private function renew(TelegramTracking $task): void
    {
        if (! $task->canRenew($this->config->integer('renewal_window_days'))) {
            throw new TrackingException('renew_too_early');
        }
        $task->update(['expires_at' => $task->expires_at->addMonthsNoOverflow($this->config->integer('duration_months'))]);
    }

    private function activationAttributes(User $user): array
    {
        $subscription = $user->activeSubscription()->first();

        return [
            'activation_plan' => AccountPlan::fromNullable($subscription?->plan)->value,
            'entitlement_until' => $subscription?->ends_at,
        ];
    }

    private function checkCapacity(User $user): void
    {
        if ($user->isBlocked() || ! $user->hasVerifiedEmail()) {
            throw new TrackingException('account_unavailable');
        }
        if ($this->capacity->activeCount($user) >= $this->config->limit($user->currentPlan())) {
            throw new TrackingException('limit');
        }
    }

    private function openTasks(User $user): Builder
    {
        return TelegramTracking::query()->forUser($user->id)->unfinished()->where('expires_at', '>', now());
    }
}
