<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\MoonShine\Support\AdminAccess;
use App\MoonShine\Support\AdminControlAnalyticsService;
use App\MoonShine\Support\AdminDashboardConfig;
use App\MoonShine\Support\AdminNavigationCatalog;
use App\MoonShine\Support\AdminRole;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\Core\ResourceContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Laravel\Pages\Page;
use MoonShine\MenuManager\Attributes\SkipMenu;
use MoonShine\Support\Enums\Color;
use MoonShine\UI\Components\FlexibleRender;
use MoonShine\UI\Components\Metrics\Wrapped\ValueMetric;

#[SkipMenu]

class Dashboard extends Page
{
    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            '#' => $this->getTitle(),
        ];
    }

    public function getTitle(): string
    {
        return $this->title ?: __('admin_dashboard.title');
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): iterable
    {
        $config = app(AdminDashboardConfig::class);
        $period = $config->normalizePeriod((int) request()->integer('period', $config->defaultPeriod));
        $analytics = app(AdminControlAnalyticsService::class);
        $access = app(AdminAccess::class);
        $user = auth('moonshine')->user();
        $moonShineUser = $user instanceof MoonshineUser ? $user : null;
        $role = $access->role($moonShineUser) ?? AdminRole::Analyst;
        $snapshot = $analytics->snapshot();
        $topModules = array_map(
            static function (array $row): array {
                if (($row['module_label'] ?? '') === 'unknown') {
                    $row['module_label'] = __('admin_dashboard.table.unknown_module');
                }

                return $row;
            },
            $analytics->topModules($period, $config->topModulesLimit),
        );
        $dailyActivity = $analytics->dailyActivity($period);

        return [
            FlexibleRender::make(
                view('moonshine.dashboard.hero'),
                [
                    'roleLabel' => $role->label(),
                    'healthStatus' => $analytics->healthStatus($snapshot),
                    'quickLinks' => $this->quickLinks($role, $access, $moonShineUser),
                    'period' => $period,
                    'allowedPeriods' => $config->periods,
                    'generatedAt' => now()->format('d.m.Y H:i'),
                ],
            ),

            ...$this->metrics($role, $snapshot),

            FlexibleRender::make(
                view('moonshine.dashboard.control-overview'),
                [
                    'snapshot' => $snapshot,
                    'topModules' => $topModules,
                    'dailyActivity' => $dailyActivity,
                    'period' => $period,
                    'healthStatus' => $analytics->healthStatus($snapshot),
                ],
            ),
        ];
    }

    /**
     * @param  array<string, int|float>  $snapshot
     * @return list<ValueMetric>
     */
    private function metrics(AdminRole $role, array $snapshot): array
    {
        $catalog = [
            'users_total' => ['registered_users', 'users', Color::BLUE],
            'users_registered_7d' => ['new_users_7d', 'user-plus', Color::INFO],
            'users_active_24h' => ['active_users_24h', 'cursor-arrow-rays', Color::PRIMARY],
            'users_paid_active' => ['paid_users_active', 'star', Color::SUCCESS],
            'requests_24h' => ['requests_24h', 'chart-bar', Color::GREEN],
            'requests_7d' => ['requests_7d', 'chart-bar-square', Color::SECONDARY],
            'modules_used_30d' => ['used_modules_30d', 'squares-2x2', Color::PURPLE],
            'avg_response_ms_24h' => ['avg_response_24h_ms', 'bolt', Color::GRAY],
            'errors_5xx_24h' => ['errors_5xx_24h', 'x-circle', Color::ERROR],
            'queue_jobs_ready' => ['queue_ready_now', 'queue-list', Color::WARNING],
            'parser_runs_active' => ['parser_runs_active', 'arrow-path', Color::INFO],
            'failed_jobs_24h' => ['failed_jobs_24h', 'exclamation-triangle', Color::ERROR],
        ];

        $keys = match ($role) {
            AdminRole::Admin => [
                'users_total',
                'users_active_24h',
                'users_paid_active',
                'requests_24h',
                'parser_runs_active',
                'failed_jobs_24h',
            ],
            AdminRole::Analyst => [
                'users_total',
                'users_registered_7d',
                'users_active_24h',
                'users_paid_active',
                'requests_7d',
                'modules_used_30d',
            ],
            AdminRole::Developer => [
                'requests_24h',
                'avg_response_ms_24h',
                'errors_5xx_24h',
                'queue_jobs_ready',
                'parser_runs_active',
                'failed_jobs_24h',
            ],
        };

        return array_map(
            static function (string $key) use ($catalog, $snapshot): ValueMetric {
                [$label, $icon, $color] = $catalog[$key];

                return ValueMetric::make(__("admin_dashboard.metrics.{$label}"))
                    ->icon($icon)
                    ->value($snapshot[$key] ?? 0)
                    ->iconColor($color)
                    ->columnSpan(4, 12);
            },
            $keys,
        );
    }

    /**
     * @return list<array{title: string, description: string, url: string}>
     */
    private function quickLinks(
        AdminRole $role,
        AdminAccess $access,
        ?MoonshineUser $user,
    ): array {
        $links = [];

        foreach (AdminNavigationCatalog::dashboardResources($role) as $resourceClass) {
            if (! $access->canViewResource($user, $resourceClass)) {
                continue;
            }

            $resource = $this->getCore()->getInstances($resourceClass);
            if (! $resource instanceof ResourceContract && ! $resource instanceof PageContract) {
                continue;
            }

            $key = AdminNavigationCatalog::resourceKey($resourceClass);
            $links[] = [
                'title' => $resource->getTitle(),
                'description' => __("admin_dashboard.quick_links.{$key}"),
                'url' => $resource->getUrl(),
            ];
        }

        return $links;
    }
}
