<?php

namespace App\Services\Monitoring;

use App\Models\MonitoringProject;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class MonitoringAccess
{
    public function limits(User $user): array
    {
        return config('monitoring.plans.'.$user->currentPlan()->value, config('monitoring.plans.free'));
    }

    public function assertUser(User $user): void
    {
        abort_if($user->isBlocked() || ! $user->hasVerifiedEmail(), 403);
    }

    public function canRun(MonitoringProject $project): bool
    {
        $user = $project->user;
        if ($user === null || $user->isBlocked() || ! $user->hasVerifiedEmail() || $project->status !== 'active') {
            return false;
        }

        // A downgrade suspends excess projects deterministically. Retained history stays readable.
        return MonitoringProject::query()->where('user_id', $user->id)->where('status', 'active')
            ->where('id', '<=', $project->id)->count() <= $this->limits($user)['active_projects'];
    }

    public function checkCapacity(User $user, string $kind, int $count): void
    {
        $this->assertUser($user);
        if ($count >= $this->limits($user)[$kind]) {
            throw ValidationException::withMessages(['monitoring' => __('monitoring.limit')]);
        }
    }
}
