<?php

namespace App\Services\Dashboard;

use App\Models\RequestLog;
use App\Models\User;
use App\Models\UserSavedQuery;
use App\Support\Activity\RequestLogRunUrlBuilder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Schema;

class SavedQueryService
{
    public function __construct(
        private readonly RequestLogRunUrlBuilder $runUrlBuilder,
    ) {
    }

    /**
     * @throws ModelNotFoundException
     */
    public function saveFromRequestLog(User $user, int $requestLogId): void
    {
        if (!Schema::hasTable('request_logs') || !Schema::hasTable('user_saved_queries')) {
            return;
        }

        $log = RequestLog::query()
            ->where('id', $requestLogId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (!is_string($log->module_key) || !is_string($log->query_preview) || trim($log->query_preview) === '') {
            return;
        }

        $payload = is_array($log->request_data) ? $log->request_data : [];
        $runUrl = $this->runUrlBuilder->build(
            path: $log->path,
            method: $log->method,
            payload: $payload,
        );

        $existing = UserSavedQuery::query()
            ->where('user_id', $user->id)
            ->where('module_key', $log->module_key)
            ->where('path', $log->path)
            ->where(function ($query) use ($runUrl, $log): void {
                if (is_string($runUrl) && $runUrl !== '') {
                    $query->where('run_url', $runUrl);

                    return;
                }

                $query->where('query_preview', $log->query_preview);
            })
            ->first();

        if ($existing instanceof UserSavedQuery) {
            $existing->update([
                'last_used_at' => now(),
                'payload' => $payload,
                'run_url' => $runUrl,
            ]);

            return;
        }

        UserSavedQuery::query()->create([
            'user_id' => $user->id,
            'module_key' => $log->module_key,
            'query_preview' => $log->query_preview,
            'method' => strtoupper((string) $log->method),
            'path' => $log->path,
            'run_url' => $runUrl,
            'payload' => $payload,
            'last_used_at' => now(),
        ]);
    }

    public function deleteForUser(User $user, UserSavedQuery $savedQuery): void
    {
        if ($savedQuery->user_id !== $user->id) {
            return;
        }

        $savedQuery->delete();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listForUser(User $user, int $limit = 40): array
    {
        if (!Schema::hasTable('user_saved_queries')) {
            return [];
        }

        return UserSavedQuery::query()
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(function (UserSavedQuery $query): array {
                $payload = is_array($query->payload) ? $query->payload : [];
                $resolvedRunUrl = $this->runUrlBuilder->build(
                    path: $query->path,
                    method: $query->method,
                    payload: $payload,
                );

                return [
                    'id' => $query->id,
                    'module_key' => $query->module_key,
                    'query_preview' => $query->query_preview,
                    'run_url' => $resolvedRunUrl ?? $query->run_url,
                    'last_used_at' => optional($query->last_used_at)?->toIso8601String(),
                    'created_at' => optional($query->created_at)?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }
}
