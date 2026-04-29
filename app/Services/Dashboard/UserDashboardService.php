<?php

namespace App\Services\Dashboard;

use App\Models\RequestLog;
use App\Models\User;
use App\Support\Activity\RequestLogSchemaInspector;
use App\Support\Dashboard\DashboardModuleRegistry;

class UserDashboardService
{
    public function __construct(
        private readonly DashboardFilterNormalizer $filterNormalizer,
        private readonly DashboardSummaryService $summaryService,
        private readonly DashboardActivityFeedService $activityFeedService,
        private readonly DashboardChartService $chartService,
        private readonly DashboardModuleRegistry $moduleRegistry,
        private readonly ModulePinService $modulePinService,
        private readonly SavedQueryService $savedQueryService,
        private readonly RequestLogSchemaInspector $schemaInspector,
    ) {
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function build(User $user, array $filters = []): array
    {
        $normalizedFilters = $this->filterNormalizer->normalize($filters);

        if (!$this->schemaInspector->hasExtendedSchema()) {
            return $this->emptyPayload($normalizedFilters);
        }

        $baseQuery = $this->baseQueryForUser($user);
        $pinnedModules = $this->modulePinService->listForUser($user);
        $pinnedLookup = array_fill_keys($pinnedModules, true);

        return [
            'summary' => $this->summaryService->buildSummary(clone $baseQuery),
            'favorite_module' => $this->summaryService->buildFavoriteModule(clone $baseQuery),
            'modules' => $this->summaryService->buildModuleCards(clone $baseQuery, $pinnedLookup),
            'activity_feed' => $this->activityFeedService->build(clone $baseQuery, $normalizedFilters),
            'chart' => $this->chartService->build(clone $baseQuery, 7),
            'saved_queries' => $this->savedQueryService->listForUser($user),
            'pinned_modules' => $pinnedModules,
            'filters' => $normalizedFilters,
            'available_modules' => $this->moduleRegistry->keys(),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function emptyPayload(array $filters): array
    {
        return [
            'summary' => [
                'total_actions' => 0,
                'actions_last_7_days' => 0,
                'actions_last_30_days' => 0,
                'active_days_last_30_days' => 0,
            ],
            'favorite_module' => null,
            'modules' => [],
            'activity_feed' => [],
            'chart' => $this->chartService->buildEmpty(7),
            'saved_queries' => [],
            'pinned_modules' => [],
            'filters' => $filters,
            'available_modules' => $this->moduleRegistry->keys(),
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<RequestLog>
     */
    private function baseQueryForUser(User $user)
    {
        return RequestLog::query()
            ->where('user_id', $user->id)
            ->whereNotNull('module_key')
            ->whereNotNull('query_preview')
            ->where('query_preview', '!=', '')
            ->whereColumn('query_preview', '!=', 'route_name');
    }
}

