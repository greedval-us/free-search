<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\MoonShine\Support\AdminControlAnalyticsService;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\Pages\Page;
use MoonShine\MenuManager\Attributes\SkipMenu;
use MoonShine\Support\Enums\Color;
use MoonShine\UI\Components\FlexibleRender;
use MoonShine\UI\Components\Heading;
use MoonShine\UI\Components\Metrics\Wrapped\ValueMetric;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;

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
        $period = (int) request()->integer('period', 7);
        $allowedPeriods = [7, 30, 90];

        if (! \in_array($period, $allowedPeriods, true)) {
            $period = 7;
        }

        $analytics = new AdminControlAnalyticsService;
        $snapshot = $analytics->snapshot();
        $topModules = array_map(
            static function (array $row): array {
                if (($row['module_label'] ?? '') === 'unknown') {
                    $row['module_label'] = __('admin_dashboard.table.unknown_module');
                }

                return $row;
            },
            $analytics->topModules($period),
        );
        $dailyActivity = $analytics->dailyActivity($period);

        return [
            Heading::make(__('admin_dashboard.sections.overview'), 3),

            ValueMetric::make(__('admin_dashboard.metrics.registered_users'))
                ->icon('users')
                ->value($snapshot['users_total'])
                ->iconColor(Color::BLUE)
                ->columnSpan(12, 12),

            ValueMetric::make(__('admin_dashboard.metrics.new_users_24h'))
                ->icon('user-plus')
                ->value($snapshot['users_registered_24h'])
                ->iconColor(Color::INFO)
                ->columnSpan(12, 12),

            ValueMetric::make(__('admin_dashboard.metrics.new_users_7d'))
                ->icon('calendar-days')
                ->value($snapshot['users_registered_7d'])
                ->iconColor(Color::PRIMARY)
                ->columnSpan(12, 12),

            ValueMetric::make(__('admin_dashboard.metrics.paid_users_active'))
                ->icon('star')
                ->value($snapshot['users_paid_active'])
                ->iconColor(Color::SUCCESS)
                ->columnSpan(12, 12),

            ValueMetric::make(__('admin_dashboard.metrics.plus_users_active'))
                ->icon('sparkles')
                ->value($snapshot['users_plus_active'])
                ->iconColor(Color::WARNING)
                ->columnSpan(12, 12),

            ValueMetric::make(__('admin_dashboard.metrics.pro_users_active'))
                ->icon('shield-check')
                ->value($snapshot['users_pro_active'])
                ->iconColor(Color::SUCCESS)
                ->columnSpan(12, 12),

            ValueMetric::make(__('admin_dashboard.metrics.blocked_users'))
                ->icon('lock-closed')
                ->value($snapshot['users_blocked'])
                ->iconColor(Color::ERROR)
                ->columnSpan(12, 12),

            ValueMetric::make(__('admin_dashboard.metrics.requests_24h'))
                ->icon('clock')
                ->value($snapshot['requests_24h'])
                ->iconColor(Color::GREEN)
                ->columnSpan(12, 12),

            ValueMetric::make(__('admin_dashboard.metrics.requests_7d'))
                ->icon('chart-bar')
                ->value($snapshot['requests_7d'])
                ->iconColor(Color::SECONDARY)
                ->columnSpan(12, 12),

            ValueMetric::make(__('admin_dashboard.metrics.used_modules_30d'))
                ->icon('squares-2x2')
                ->value($snapshot['modules_used_30d'])
                ->iconColor(Color::PURPLE)
                ->columnSpan(12, 12),

            ValueMetric::make(__('admin_dashboard.metrics.errors_5xx_24h'))
                ->icon('x-circle')
                ->value($snapshot['errors_5xx_24h'])
                ->iconColor(Color::YELLOW)
                ->columnSpan(12, 12),

            ValueMetric::make(__('admin_dashboard.metrics.avg_response_24h_ms'))
                ->icon('bolt')
                ->value($snapshot['avg_response_ms_24h'])
                ->iconColor(Color::GRAY)
                ->columnSpan(12, 12),

            ValueMetric::make(__('admin_dashboard.metrics.queue_ready_now'))
                ->icon('queue-list')
                ->value($snapshot['queue_jobs_ready'])
                ->iconColor(Color::WARNING)
                ->columnSpan(12, 12),

            ValueMetric::make(__('admin_dashboard.metrics.failed_jobs_24h'))
                ->icon('exclamation-triangle')
                ->value($snapshot['failed_jobs_24h'])
                ->iconColor(Color::ERROR)
                ->columnSpan(12, 12),

            Heading::make(__('admin_dashboard.sections.visual_analytics'), 3),

            FlexibleRender::make(
                view('moonshine.dashboard.control-overview'),
                [
                    'snapshot' => $snapshot,
                    'topModules' => $topModules,
                    'dailyActivity' => $dailyActivity,
                    'period' => $period,
                    'allowedPeriods' => $allowedPeriods,
                ],
            ),

            Heading::make(__('admin_dashboard.sections.most_used_modules', ['days' => $period]), 4),

            TableBuilder::make(
                [
                    Text::make(__('admin_dashboard.table.module'), 'module_label'),
                    Number::make(__('admin_dashboard.table.requests'), 'requests_count'),
                    Number::make(__('admin_dashboard.table.unique_users'), 'users_count'),
                    Number::make(__('admin_dashboard.table.errors_4xx'), 'errors_4xx'),
                    Number::make(__('admin_dashboard.table.errors_5xx'), 'errors_5xx'),
                ],
                $topModules,
            )->simple()->withNotFound(),

            Heading::make(__('admin_dashboard.sections.daily_activity', ['days' => $period]), 4),

            TableBuilder::make(
                [
                    Text::make(__('admin_dashboard.table.date'), 'date'),
                    Number::make(__('admin_dashboard.table.registrations'), 'registrations_count'),
                    Number::make(__('admin_dashboard.table.requests'), 'requests_count'),
                    Number::make(__('admin_dashboard.table.active_users'), 'active_users_count'),
                ],
                $dailyActivity,
            )->simple()->withNotFound(),
        ];
    }
}
