<?php

namespace App\Modules\Telegram\Analytics;

use App\Modules\Telegram\Core\Contracts\TelegramGatewayInterface;
use App\Modules\Telegram\Presenters\TelegramMessagePresenter;
use App\Modules\Telegram\DTO\Response\Info\ChannelInfoDTO;
use App\Modules\Telegram\Support\TelegramConfig;
use Carbon\Carbon;

class TelegramAnalyticsService
{
    public function __construct(
        private readonly TelegramGatewayInterface $telegramService,
        private readonly TelegramMessagePresenter $messagePresenter,
        private readonly TelegramMessageRangeLoader $messageRangeLoader,
        private readonly TelegramAnalyticsSummaryBuilder $summaryBuilder,
        private readonly TelegramConfig $config,
    ) {
    }

    /**
     * Build analytics payload for a channel and a time range.
     *
     * @param string|null $scorePriority
     * @param string|null $keyword
     * @return array<string, mixed>
     */
    public function build(
        string $chatUsername,
        Carbon $dateFrom,
        Carbon $dateTo,
        ?string $scorePriority = null,
        ?string $keyword = null
    ): array {
        $scoreProfile = $this->resolveScoreProfile($scorePriority);
        $weights = $scoreProfile['weights'];
        $groupBy = $this->resolveGroupBy($dateFrom, $dateTo);

        $messages = $this->messageRangeLoader->load($chatUsername, $dateFrom, $dateTo, $keyword);
        $items = $this->messagePresenter->presentMessages($messages, $chatUsername);
        usort($items, static fn (array $left, array $right): int => ($left['date'] ?? 0) <=> ($right['date'] ?? 0));

        $timeline = $this->buildTimeline($dateFrom, $dateTo, $groupBy);
        $summary = $this->summaryBuilder->build($items, $timeline, $chatUsername, $weights, $groupBy);
        $groupInfo = $this->buildGroupInfo($chatUsername);

        return [
            'range' => [
                'chatUsername' => $chatUsername,
                'dateFrom' => $dateFrom->toIso8601String(),
                'dateTo' => $dateTo->toIso8601String(),
                'label' => $dateFrom->format('d.m.Y') . ' - ' . $dateTo->format('d.m.Y'),
                'periodDays' => max(1, min($this->periodMaxDays(), $dateFrom->diffInDays($dateTo) + 1)),
                'groupBy' => $groupBy,
                'keyword' => $keyword,
            ],
            'groupInfo' => $groupInfo,
            'score' => $scoreProfile,
            'summary' => $summary,
        ];
    }

    private function resolveGroupBy(Carbon $dateFrom, Carbon $dateTo): string
    {
        return $dateFrom->diffInHours($dateTo) <= $this->groupByHourThresholdHours() ? 'hour' : 'day';
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function buildTimeline(Carbon $dateFrom, Carbon $dateTo, string $groupBy): array
    {
        $timeline = [];
        $cursor = $dateFrom->copy();
        $intervalMethod = $groupBy === 'hour' ? 'addHour' : 'addDay';
        $format = $groupBy === 'hour' ? 'Y-m-d H:00' : 'Y-m-d';
        $labelFormat = $groupBy === 'hour' ? 'H:00' : 'd.m';

        while ($cursor <= $dateTo) {
            $key = $cursor->format($format);

            $timeline[$key] = [
                'key' => $key,
                'label' => $cursor->format($labelFormat),
                'messages' => 0,
                'views' => 0,
                'forwards' => 0,
                'replies' => 0,
                'reactions' => 0,
                'gifts' => 0,
                'media' => 0,
                'interactions' => 0,
            ];

            $cursor->{$intervalMethod}();
        }

        return $timeline;
    }

    /**
     * @return array{priority: string, weights: array{views: float, forwards: float, replies: float, reactions: float, gifts: float}}
     */
    private function resolveScoreProfile(?string $priority): array
    {
        $profiles = $this->scoreProfiles();
        $priority = strtolower(trim((string) $priority));

        if (!array_key_exists($priority, $profiles)) {
            $priority = 'balanced';
        }

        return [
            'priority' => $priority,
            'weights' => $profiles[$priority],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildGroupInfo(string $chatUsername): ?array
    {
        $info = $this->telegramService->getInfo($chatUsername);
        if (!$info instanceof ChannelInfoDTO || $info->chat === null) {
            return null;
        }

        $chat = $info->chat;
        $full = $info->full;

        return [
            'id' => $chat->id > 0 ? $chat->id : null,
            'title' => trim((string) $chat->title) !== '' ? $chat->title : $chatUsername,
            'username' => $chat->username,
            'type' => $this->resolveGroupType($chat->broadcast, $chat->megagroup, $chat->forum, $chat->gigagroup),
            'description' => $full?->about ?: $chat->about,
            'participantsCount' => $full?->participants_count ?? $chat->participants_count,
            'onlineCount' => $full?->online_count,
            'verified' => (bool) $chat->verified,
            'restricted' => (bool) $chat->restricted,
            'scam' => (bool) $chat->scam,
            'createdAt' => $chat->date > 0 ? $chat->date : null,
            'linkedChatId' => $full?->linked_chat_id,
            'canViewStats' => (bool) ($full?->can_view_stats ?? false),
        ];
    }

    private function resolveGroupType(bool $broadcast, bool $megagroup, bool $forum, bool $gigagroup): string
    {
        if ($gigagroup) {
            return 'gigagroup';
        }

        if ($forum) {
            return 'forum';
        }

        if ($broadcast) {
            return 'channel';
        }

        if ($megagroup) {
            return 'group';
        }

        return 'chat';
    }

    private function groupByHourThresholdHours(): int
    {
        return $this->config->analyticsGroupByHourThresholdHours();
    }

    private function periodMaxDays(): int
    {
        return $this->config->analyticsPeriodMaxDays();
    }

    /**
     * @return array<string, array{views: float, forwards: float, replies: float, reactions: float, gifts: float}>
     */
    private function scoreProfiles(): array
    {
        return $this->config->analyticsScoreProfiles();
    }
}
