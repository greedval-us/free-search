<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\MoonShine\Support\AdminControlAnalyticsService;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\Pages\Page;
use MoonShine\MenuManager\Attributes\SkipMenu;
use MoonShine\Support\Enums\Color;
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
        return $this->title ?: 'Dashboard';
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): iterable
    {
        $analytics = new AdminControlAnalyticsService();
        $snapshot = $analytics->snapshot();
        $topModules = $analytics->topModules();
        $dailyActivity = $analytics->dailyActivity();

        return [
            ValueMetric::make('Registered users')
                ->icon('users')
                ->value($snapshot['users_total'])
                ->iconColor(Color::BLUE),

            ValueMetric::make('New users 24h')
                ->icon('user-plus')
                ->value($snapshot['users_registered_24h'])
                ->iconColor(Color::INFO),

            ValueMetric::make('New users 7d')
                ->icon('calendar-days')
                ->value($snapshot['users_registered_7d'])
                ->iconColor(Color::PRIMARY),

            ValueMetric::make('Premium users (active)')
                ->icon('star')
                ->value($snapshot['users_premium_active'])
                ->iconColor(Color::SUCCESS),

            ValueMetric::make('Blocked users')
                ->icon('lock-closed')
                ->value($snapshot['users_blocked'])
                ->iconColor(Color::ERROR),

            ValueMetric::make('Requests in 24h')
                ->icon('clock')
                ->value($snapshot['requests_24h'])
                ->iconColor(Color::GREEN),

            ValueMetric::make('Requests in 7d')
                ->icon('chart-bar')
                ->value($snapshot['requests_7d'])
                ->iconColor(Color::SECONDARY),

            ValueMetric::make('Used modules 30d')
                ->icon('squares-2x2')
                ->value($snapshot['modules_used_30d'])
                ->iconColor(Color::PURPLE),

            ValueMetric::make('Errors 5xx in 24h')
                ->icon('x-circle')
                ->value($snapshot['errors_5xx_24h'])
                ->iconColor(Color::YELLOW),

            ValueMetric::make('Avg response 24h (ms)')
                ->icon('bolt')
                ->value($snapshot['avg_response_ms_24h'])
                ->iconColor(Color::GRAY),

            Heading::make('Most used modules (30 days)', 4),

            TableBuilder::make(
                [
                    Text::make('Module', 'module_label'),
                    Number::make('Requests', 'requests_count'),
                    Number::make('Unique users', 'users_count'),
                    Number::make('Errors 4xx', 'errors_4xx'),
                    Number::make('Errors 5xx', 'errors_5xx'),
                ],
                $topModules,
            )->simple()->withNotFound(),

            Heading::make('Daily activity (7 days)', 4),

            TableBuilder::make(
                [
                    Text::make('Date', 'date'),
                    Number::make('Registrations', 'registrations_count'),
                    Number::make('Requests', 'requests_count'),
                    Number::make('Active users', 'active_users_count'),
                ],
                $dailyActivity,
            )->simple()->withNotFound(),
        ];
    }
}
