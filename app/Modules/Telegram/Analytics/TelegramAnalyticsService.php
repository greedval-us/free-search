<?php

namespace App\Modules\Telegram\Analytics;

use App\Modules\Telegram\Core\Contracts\TelegramGatewayInterface;
use App\Modules\Telegram\Presenters\TelegramMessagePresenter;
use App\Modules\Telegram\DTO\Response\Info\ChannelInfoDTO;
use Carbon\Carbon;

class TelegramAnalyticsService
{
    private const MAX_PAGES = 20;
    private const PAGE_LIMIT = 100;
    private const SCORE_PROFILES = [
        'balanced' => [
            'views' => 1.0,
            'forwards' => 5.0,
            'replies' => 8.0,
            'reactions' => 2.0,
            'gifts' => 10.0,
        ],
        'reach' => [
            'views' => 3.0,
            'forwards' => 4.0,
            'replies' => 2.0,
            'reactions' => 1.0,
            'gifts' => 2.0,
        ],
        'discussion' => [
            'views' => 1.0,
            'forwards' => 3.0,
            'replies' => 12.0,
            'reactions' => 2.0,
            'gifts' => 3.0,
        ],
        'virality' => [
            'views' => 1.0,
            'forwards' => 10.0,
            'replies' => 6.0,
            'reactions' => 3.0,
            'gifts' => 5.0,
        ],
    ];

    public function __construct(
        private readonly TelegramGatewayInterface $telegramService,
        private readonly TelegramMessagePresenter $messagePresenter,
        private readonly TelegramAnalyticsSummaryBuilder $summaryBuilder,
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

        $messages = $this->loadMessages($chatUsername, $dateFrom, $dateTo, $keyword);
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
                'periodDays' => max(1, min(7, $dateFrom->diffInDays($dateTo) + 1)),
                'groupBy' => $groupBy,
                'keyword' => $keyword,
            ],
            'groupInfo' => $groupInfo,
            'score' => $scoreProfile,
            'summary' => $summary,
        ];
    }

    /**
     * @return array<int, object>
     */
    private function loadMessages(string $chatUsername, Carbon $dateFrom, Carbon $dateTo, ?string $keyword = null): array
    {
        $messages = [];
        $seenIds = [];
        $seenOffsets = [];
        $offsetId = 0;
        $minDate = $dateFrom->timestamp;
        $maxDate = $dateTo->timestamp;
        $keyword = trim((string) $keyword);

        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            $dto = $this->telegramService->getMessages([
                'peer' => $chatUsername,
                'q' => $keyword,
                'limit' => self::PAGE_LIMIT,
                'offset_id' => $offsetId,
                'min_date' => $minDate,
                'max_date' => $maxDate,
            ]);

            if ($dto === null || empty($dto->messages)) {
                break;
            }

            $oldestReached = false;

            foreach ($dto->messages as $message) {
                $messageId = (int) ($message->id ?? 0);
                $messageDate = (int) ($message->date ?? 0);

                if ($messageId <= 0) {
                    continue;
                }

                if ($messageDate < $minDate) {
                    $oldestReached = true;
                    break;
                }

                if ($messageDate > $maxDate) {
                    continue;
                }

                if (isset($seenIds[$messageId])) {
                    continue;
                }

                $seenIds[$messageId] = true;
                $messages[] = $message;
            }

            if ($oldestReached) {
                break;
            }

            $nextOffsetId = $this->messagePresenter->resolveNextOffsetId($dto->messages);

            if ($nextOffsetId === null || count($dto->messages) < self::PAGE_LIMIT) {
                break;
            }

            if (isset($seenOffsets[$nextOffsetId])) {
                break;
            }

            $seenOffsets[$nextOffsetId] = true;
            $offsetId = $nextOffsetId;
        }

        return $messages;
    }

    private function resolveGroupBy(Carbon $dateFrom, Carbon $dateTo): string
    {
        return $dateFrom->diffInHours($dateTo) <= 36 ? 'hour' : 'day';
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
        $priority = strtolower(trim((string) $priority));

        if (!array_key_exists($priority, self::SCORE_PROFILES)) {
            $priority = 'balanced';
        }

        return [
            'priority' => $priority,
            'weights' => self::SCORE_PROFILES[$priority],
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
}
