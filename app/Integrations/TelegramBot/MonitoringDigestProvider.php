<?php

namespace App\Integrations\TelegramBot;

use App\Models\MonitoringReport;
use App\Modules\TelegramBot\Domain\Contracts\DigestProvider;
use App\Modules\TelegramBot\Domain\DTO\BotButton;
use App\Modules\TelegramBot\Domain\DTO\BotScreen;
use App\Modules\TelegramBot\Domain\Exceptions\ArtifactUnavailable;
use App\Modules\TelegramBot\Models\BotDelivery;
use App\Modules\TelegramBot\Models\BotLink;
use App\Modules\TelegramBot\Support\BotConfig;
use App\Services\Monitoring\MonitoringAccess;

final readonly class MonitoringDigestProvider implements DigestProvider
{
    public function __construct(private BotConfig $config, private MonitoringAccess $access) {}

    public function allows(BotDelivery $delivery, BotLink $link): bool
    {
        $report = $this->report($delivery, $link);
        if ($report === null) {
            return false;
        }
        if (! $delivery->automatic) {
            return $delivery->kind === 'monitoring_document';
        }
        $project = $report->project;
        $configuration = $report->configuration ?? [];
        if ($project === null || $project->status !== 'active' || ! $project->delivery_enabled
            || (int) $project->user_id !== (int) $link->user_id
            || (int) $project->generation !== (int) ($configuration['generation'] ?? 0)
            || (int) ($delivery->payload['version'] ?? 0) !== (int) $report->version
            || ! $link->notifications_enabled || ! $this->access->canRun($project)) {
            return false;
        }
        if ($report->schedule_id === null && ($configuration['schedule_generation'] ?? null) !== null) {
            return false;
        }
        if ($report->schedule_id !== null) {
            $schedule = $report->schedule;
            if ($schedule === null || ! $schedule->enabled || ! $schedule->delivery_enabled
                || (int) $schedule->project_id !== (int) $project->id
                || (int) $schedule->generation !== (int) ($configuration['schedule_generation'] ?? 0)) {
                return false;
            }
        }
        if ($report->status === 'empty' && $project->empty_delivery === 'skip') {
            return false;
        }

        return $delivery->kind !== 'monitoring_document' || (bool) $project->attach_files;
    }

    public function screen(BotDelivery $delivery, BotLink $link): BotScreen
    {
        $report = $this->report($delivery, $link);
        if ($report === null) {
            throw new ArtifactUnavailable;
        }
        $summary = $report->summary ?? [];
        $title = (string) ($summary['title'] ?? $report->configuration['name'] ?? __('monitoring_bot.title', [], $link->locale));
        $lines = [mb_substr($title, 0, 160), $report->start_at->timezone($report->timezone)->format('Y-m-d H:i').' — '.$report->end_at->timezone($report->timezone)->format('Y-m-d H:i').' ('.$report->timezone.')'];
        $lines[] = __('monitoring_bot.count', ['count' => (int) ($summary['count'] ?? 0)], $link->locale);
        if ($report->status === 'partial') {
            $lines[] = __('monitoring_bot.partial', [], $link->locale);
        } elseif ($report->status === 'empty') {
            $lines[] = __('monitoring_bot.empty', [], $link->locale);
        }
        if (! empty($summary['introduction'])) {
            $lines[] = mb_substr((string) $summary['introduction'], 0, 500);
        }
        foreach (array_slice($summary['bullets'] ?? [], 0, 5) as $bullet) {
            $text = is_array($bullet) ? (string) ($bullet['text'] ?? '') : (string) $bullet;
            if ($text !== '') {
                $lines[] = '• '.mb_substr($text, 0, 400);
            }
            foreach (array_slice(is_array($bullet) ? ($bullet['urls'] ?? []) : [], 0, 1) as $url) {
                if (is_string($url) && filter_var($url, FILTER_VALIDATE_URL) !== false
                    && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
                    $lines[] = mb_substr($url, 0, 400);
                }
            }
        }

        return new BotScreen(mb_substr(implode("\n\n", $lines), 0, 3900), [
            new BotButton(__('monitoring_bot.full_report', [], $link->locale), 'url', $this->config->siteUrl('/monitoring/reports/'.$report->id)),
            new BotButton(__('monitoring_bot.download_xlsx', [], $link->locale), 'action', 'send', ['k' => 'monitoring_document', 'i' => $report->id, 'f' => 'xlsx']),
            new BotButton(__('monitoring_bot.download_json', [], $link->locale), 'action', 'send', ['k' => 'monitoring_document', 'i' => $report->id, 'f' => 'json']),
            new BotButton(__('monitoring_bot.settings', [], $link->locale), 'url', $this->config->siteUrl('/monitoring/projects/'.$report->project_id)),
        ]);
    }

    public function record(BotDelivery $delivery, string $status): void
    {
        if ($delivery->kind === 'monitoring_digest') {
            $userId = BotLink::query()->whereKey($delivery->link_id)->value('user_id');
            if ($userId !== null) {
                MonitoringReport::query()->whereKey((int) $delivery->reference)->where('user_id', $userId)->update(['delivery_status' => $status]);
            }
        }
    }

    private function report(BotDelivery $delivery, BotLink $link): ?MonitoringReport
    {
        return MonitoringReport::query()->with(['project', 'schedule'])->where('user_id', $link->user_id)
            ->whereIn('status', ['completed', 'partial', 'empty'])
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->find((int) $delivery->reference);
    }
}
