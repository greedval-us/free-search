<?php

namespace App\Modules\ParserSupport;

use App\Models\ParserRun;
use App\Modules\ParserSupport\Enums\ParserRunStatus;
use Illuminate\Support\Collection;

final class ParserRunHistoryRepository
{
    public function activeForUser(int $userId, string $moduleKey): ?ParserRun
    {
        return ParserRun::query()
            ->where('user_id', $userId)
            ->where('module', $moduleKey)
            ->where('status', ParserRunStatus::Running->value)
            ->latest('started_at')
            ->latest('id')
            ->first();
    }

    /**
     * @return Collection<int, ParserRun>
     */
    public function forUser(int $userId, string $moduleKey): Collection
    {
        return ParserRun::query()
            ->where('user_id', $userId)
            ->where('module', $moduleKey)
            ->where(function ($query): void {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest('started_at')
            ->latest('id')
            ->limit((int) config('osint.parser_runs.history_limit', 20))
            ->get();
    }
}
