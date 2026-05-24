<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Models\RequestLog;
use App\Models\User;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\Pages\Page;
use MoonShine\MenuManager\Attributes\SkipMenu;
use MoonShine\Support\Enums\Color;
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
        return $this->title ?: 'Dashboard';
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): iterable
    {
        return [
            ValueMetric::make('Registered users')
                ->icon('users')
                ->value(static fn (): int => User::query()->count())
                ->iconColor(Color::BLUE),

            ValueMetric::make('Blocked users')
                ->icon('no-symbol')
                ->value(static fn (): int => User::query()->where('is_blocked', true)->count())
                ->iconColor(Color::RED),

            ValueMetric::make('Requests in 24h')
                ->icon('clock')
                ->value(static fn (): int => RequestLog::query()->where('created_at', '>=', now()->subDay())->count())
                ->iconColor(Color::GREEN),

            ValueMetric::make('Errors 5xx in 24h')
                ->icon('x-circle')
                ->value(static fn (): int => RequestLog::query()
                    ->where('created_at', '>=', now()->subDay())
                    ->whereBetween('status_code', [500, 599])
                    ->count())
                ->iconColor(Color::YELLOW),
        ];
    }
}
