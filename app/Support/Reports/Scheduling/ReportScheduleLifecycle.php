<?php

namespace App\Support\Reports\Scheduling;

use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

/** Owner-first locking for schedule mutations; source access and calendar policy stay in adapters. */
final class ReportScheduleLifecycle
{
    /**
     * @template T of Model
     *
     * @param  class-string<T>  $model
     * @param  Closure(User, T): void  $resume
     */
    public function change(User $user, string $model, int $id, string $action, Closure $resume, Throwable $invalidAction): void
    {
        DB::transaction(function () use ($user, $model, $id, $action, $resume, $invalidAction): void {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $schedule = $model::query()->where('user_id', $locked->id)->lockForUpdate()->findOrFail($id);
            if ($action === 'pause') {
                $schedule->update(['enabled' => false]);

                return;
            }
            if ($action !== 'resume') {
                throw $invalidAction;
            }
            $resume($locked, $schedule);
        });
    }

    /** @param class-string<Model> $model */
    public function destroy(User $user, string $model, int $id): void
    {
        DB::transaction(function () use ($user, $model, $id): void {
            User::query()->lockForUpdate()->findOrFail($user->id);
            $schedule = $model::query()->where('user_id', $user->id)->lockForUpdate()->findOrFail($id);
            $schedule->update(['enabled' => false]);
            $schedule->delete();
        });
    }
}
