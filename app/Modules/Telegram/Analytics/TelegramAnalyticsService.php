<?php

namespace App\Modules\Telegram\Analytics;

use App\Modules\Telegram\Presenters\TelegramMessagePresenter;
use App\Modules\Telegram\TelegramService;
use Carbon\Carbon;
use Illuminate\Support\Str;

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
        private readonly TelegramService $telegramService,
        private readonly TelegramMessagePresenter $messagePresenter,
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

        $messages = $this->loadMessages($chatUsername, $dateFrom, $dateTo, $keyword);
        $items = $this->messagePresenter->presentMessages($messages, $chatUsername);

        usort($items, static fn (array $left, array $right): int => ($left['date'] ?? 0) <=> ($right['date'] ?? 0));

        $groupBy = $this->resolveGroupBy($dateFrom, $dateTo);
        $timeline = $this->buildTimeline($dateFrom, $dateTo, $groupBy);
        $summary = $this->buildSummary($items, $timeline, $chatUsername, $weights);

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
     * @param array<int, array<string, mixed>> $items
     * @param array<string, array<string, mixed>> $timeline
     * @param array{views: float, forwards: float, replies: float, reactions: float, gifts: float} $weights
     * @return array<string, mixed>
     */
    private function buildSummary(array $items, array $timeline, string $chatUsername, array $weights): array
    {
        $totals = [
            'messages' => 0,
            'views' => 0,
            'forwards' => 0,
            'replies' => 0,
            'reactions' => 0,
            'gifts' => 0,
            'mediaPosts' => 0,
            'uniqueAuthors' => 0,
            'avgViewsPerPost' => 0.0,
            'avgInteractionsPerPost' => 0.0,
        ];

        $authorIds = [];
        $mediaCounts = [];
        $reactionCounts = [];
        $topPosts = [];

        foreach ($items as $item) {
            $date = (int) ($item['date'] ?? 0);
            $views = max(0, (int) ($item['views'] ?? 0));
            $forwards = max(0, (int) ($item['forwards'] ?? 0));
            $replies = max(0, (int) ($item['repliesCount'] ?? 0));
            $reactionTotal = 0;
            $giftCount = !empty($item['gifts']['hasGift']) ? 1 : 0;
            $mediaType = (string) ($item['media']['type'] ?? 'none');

            foreach ($item['reactions'] ?? [] as $reaction) {
                $count = max(0, (int) ($reaction['count'] ?? 0));
                $reactionTotal += $count;

                $reactionLabel = trim((string) ($reaction['emoji'] ?? $reaction['key'] ?? 'Reaction'));
                if ($reactionLabel === '') {
                    $reactionLabel = 'Reaction';
                }

                $reactionCounts[$reactionLabel] = ($reactionCounts[$reactionLabel] ?? 0) + $count;
            }

            $bucketKey = $this->bucketKey($date, array_key_first($timeline), $timeline);

            if ($bucketKey !== null) {
                $timeline[$bucketKey]['messages']++;
                $timeline[$bucketKey]['views'] += $views;
                $timeline[$bucketKey]['forwards'] += $forwards;
                $timeline[$bucketKey]['replies'] += $replies;
                $timeline[$bucketKey]['reactions'] += $reactionTotal;
                $timeline[$bucketKey]['gifts'] += $giftCount;
                $timeline[$bucketKey]['media'] += $mediaType !== 'none' ? 1 : 0;
                $timeline[$bucketKey]['interactions'] += $forwards + $replies + $reactionTotal + $giftCount;
            }

            $totals['messages']++;
            $totals['views'] += $views;
            $totals['forwards'] += $forwards;
            $totals['replies'] += $replies;
            $totals['reactions'] += $reactionTotal;
            $totals['gifts'] += $giftCount;
            $totals['mediaPosts'] += $mediaType !== 'none' ? 1 : 0;

            $authorId = $item['authorId'] ?? null;
            if (is_int($authorId) && $authorId > 0) {
                $authorIds[$authorId] = true;
            }

            if ($mediaType !== 'none') {
                $mediaCounts[$mediaType] = ($mediaCounts[$mediaType] ?? 0) + 1;
            }

            $score = ($views * $weights['views'])
                + ($forwards * $weights['forwards'])
                + ($replies * $weights['replies'])
                + ($reactionTotal * $weights['reactions'])
                + ($giftCount * $weights['gifts']);
            $topPosts[] = [
                'id' => (int) ($item['id'] ?? 0),
                'date' => $date,
                'message' => Str::limit(trim((string) ($item['message'] ?? '')), 180),
                'telegramUrl' => $item['telegramUrl'] ?? null,
                'views' => $views,
                'forwards' => $forwards,
                'replies' => $replies,
                'reactions' => $reactionTotal,
                'mediaLabel' => $item['media']['label'] ?? null,
                'score' => round($score, 2),
            ];
        }

        ksort($timeline);
        $totals['uniqueAuthors'] = count($authorIds);

        if ($totals['messages'] > 0) {
            $totals['avgViewsPerPost'] = round($totals['views'] / $totals['messages'], 2);
            $totals['avgInteractionsPerPost'] = round(
                ($totals['forwards'] + $totals['replies'] + $totals['reactions'] + $totals['gifts']) / $totals['messages'],
                2
            );
        }

        usort($topPosts, static fn (array $left, array $right): int => $right['score'] <=> $left['score']);

        return [
            'totals' => $totals,
            'timeline' => array_values($timeline),
            'topMedia' => $this->buildDistribution($mediaCounts),
            'topReactions' => $this->buildDistribution($reactionCounts),
            'topPosts' => array_slice($topPosts, 0, 5),
            'chatUsername' => $chatUsername,
        ];
    }

    /**
     * @param array<string, int> $counts
     * @return array<int, array<string, mixed>>
     */
    private function buildDistribution(array $counts): array
    {
        arsort($counts);
        $total = array_sum($counts);
        $distribution = [];

        foreach (array_slice($counts, 0, 5, true) as $label => $count) {
            $distribution[] = [
                'key' => $label,
                'label' => $label,
                'count' => $count,
                'share' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ];
        }

        return $distribution;
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
     * @param array<int, array<string, mixed>> $timeline
     */
    private function bucketKey(int $timestamp, string $firstKey, array $timeline): ?string
    {
        $sample = $timeline[$firstKey] ?? null;
        if (!is_array($sample)) {
            return null;
        }

        $isHour = str_contains((string) ($sample['label'] ?? ''), ':');

        return $isHour
            ? Carbon::createFromTimestamp($timestamp, config('app.timezone'))->format('Y-m-d H:00')
            : Carbon::createFromTimestamp($timestamp, config('app.timezone'))->format('Y-m-d');
    }
}
