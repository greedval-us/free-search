<?php

declare(strict_types=1);

namespace App\MoonShine\Support;

use App\Modules\TelegramBot\Models\BotDelivery;
use App\Modules\TelegramBot\Models\BotLink;
use App\Modules\TelegramBot\Models\LinkRequest;
use App\Modules\TelegramBot\Support\BotConfig;
use App\MoonShine\Support\Formatting\TelegramBotFormatter;
use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Support\Carbon;
use Throwable;

final readonly class TelegramBotAnalyticsService
{
    public function __construct(
        private BotConfig $config,
        private AdminDashboardConfig $dashboard,
        private Factory $queues,
        private TelegramBotFormatter $formatter,
    ) {}

    public function snapshot(int $requestedPeriod): array
    {
        $retention = $this->config->integer('delivery_retention_days');
        $periods = array_values(array_unique(array_map(
            static fn (int $days): int => min($days, $retention),
            $this->dashboard->periods,
        )));
        $period = in_array($requestedPeriod, $periods, true)
            ? $requestedPeriod
            : min($this->dashboard->defaultPeriod, $retention);
        $end = now();
        $start = $end->copy()->startOfDay()->subDays($period - 1);
        $links = BotLink::query()->selectRaw(
            'COUNT(*) AS total, SUM(notifications_enabled) AS notifications, SUM(exports_enabled) AS exports, SUM(broadcasts_enabled) AS broadcasts',
        )->first();
        $deliveries = BotDelivery::query()->whereBetween('created_at', [$start, $end]);
        $statuses = (clone $deliveries)->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
        $kinds = (clone $deliveries)->selectRaw('kind, COUNT(*) AS total')->groupBy('kind')->pluck('total', 'kind');
        $daily = (clone $deliveries)
            ->selectRaw('DATE(created_at) AS day, COUNT(*) AS total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS sent', [BotDelivery::SENT])
            ->groupByRaw('DATE(created_at)')->orderBy('day')->get()->keyBy('day');
        $activity = [];
        for ($date = $start->copy(); $date->lte($end); $date = $date->addDay()) {
            $row = $daily->get($date->toDateString());
            $activity[] = ['date' => $date->toDateString(), 'total' => (int) ($row?->total ?? 0), 'sent' => (int) ($row?->sent ?? 0)];
        }
        $total = (int) $statuses->sum();
        $sent = (int) $statuses->get(BotDelivery::SENT, 0);

        return [
            'period' => $period,
            'periods' => $periods,
            'retention_days' => $retention,
            'configured' => $this->config->active() && TelegraphBot::query()->whereKey($this->config->botId())->exists(),
            'username' => $this->config->username(),
            'links' => (int) $links->total,
            'new_links' => BotLink::query()->whereBetween('created_at', [$start, $end])->count(),
            'pending_links' => LinkRequest::query()->where('expires_at', '>', $end)->count(),
            'consents' => [
                'notifications' => (int) $links->notifications,
                'exports' => (int) $links->exports,
                'broadcasts' => (int) $links->broadcasts,
            ],
            'deliveries' => $total,
            'sent_share' => $total > 0 ? round($sent * 100 / $total, 1) : null,
            'statuses' => $this->buckets($this->formatter->statuses(), $statuses->all()),
            'kinds' => $this->buckets($this->formatter->kinds(), $kinds->all()),
            'daily' => $activity,
            'daily_max' => max(1, ...array_column($activity, 'total')),
            'generated_at' => $end,
        ];
    }

    public function diagnostics(): array
    {
        $connection = (string) $this->config->get('queue.connection');
        $queue = (string) $this->config->get('queue.name');
        $driver = (string) config('queue.connections.'.$connection.'.driver');
        $size = null;
        $queueState = 'unsupported';
        if (in_array($driver, ['database', 'redis', 'beanstalkd', 'sqs'], true)) {
            try {
                $size = $this->queues->connection($connection)->size($queue);
                $queueState = 'available';
            } catch (Throwable) {
                $queueState = 'unavailable';
            }
        }
        $pending = BotDelivery::query()->where('status', BotDelivery::PENDING);
        $warningMinutes = $this->config->integer('queue.retry_window_minutes');
        $oldest = (clone $pending)->min('created_at');
        $lastSent = BotDelivery::query()->where('status', BotDelivery::SENT)->max('sent_at');

        return [
            'connection' => $connection,
            'queue' => $queue,
            'driver' => $driver,
            'queue_state' => $queueState,
            'queue_size' => $size,
            'pending' => (clone $pending)->count(),
            'stale' => (clone $pending)->where('created_at', '<=', now()->subMinutes($warningMinutes))->count(),
            'warning_minutes' => $warningMinutes,
            'oldest_pending_at' => $oldest === null ? null : Carbon::parse($oldest),
            'last_sent_at' => $lastSent === null ? null : Carbon::parse($lastSent),
        ];
    }

    private function buckets(array $labels, array $counts): array
    {
        $rows = [];
        foreach ($labels as $key => $label) {
            $rows[] = ['label' => $label, 'count' => (int) ($counts[$key] ?? 0)];
        }
        $other = array_sum(array_diff_key($counts, $labels));
        if ($other > 0) {
            $rows[] = ['label' => __('admin_panel.values.unknown'), 'count' => (int) $other];
        }

        return $rows;
    }
}
