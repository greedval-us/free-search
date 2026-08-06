<?php

namespace App\Modules\Bluesky\Parser;

use App\Models\ParserRun;
use Illuminate\Support\Collection;

final class BlueskyParserHistoryRepository
{
    /**
     * @return Collection<int, ParserRun>
     */
    public function forUser(int $userId): Collection
    {
        return ParserRun::query()
            ->where('user_id', $userId)
            ->where('module', BlueskyParserApplicationService::MODULE_KEY)
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
