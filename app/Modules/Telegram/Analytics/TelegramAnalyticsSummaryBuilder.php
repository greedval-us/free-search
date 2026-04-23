<?php

namespace App\Modules\Telegram\Analytics;

use Carbon\Carbon;
use Illuminate\Support\Str;

class TelegramAnalyticsSummaryBuilder
{
    public function __construct(
        private readonly TelegramAnalyticsFunnelCalculator $funnelCalculator,
        private readonly TelegramAnalyticsAudienceCalculator $audienceCalculator,
        private readonly TelegramAnalyticsOpinionLeadersBuilder $opinionLeadersBuilder,
        private readonly TelegramAnalyticsFraudCalculator $fraudCalculator,
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<string, array<string, mixed>> $timeline
     * @param array{views: float, forwards: float, replies: float, reactions: float, gifts: float} $weights
     * @return array<string, mixed>
     */
    public function build(
        array $items,
        array $timeline,
        string $chatUsername,
        array $weights,
        string $groupBy
    ): array {
        $totals = $this->initialTotals();
        $authorIds = [];
        $mediaCounts = [];
        $reactionCounts = [];
        $topPosts = [];
        $authorStats = [];
        $authorDailyStats = [];
        $hourlyActivity = array_fill(0, 24, 0);
        $funnelCandidates = [];
        $fraudPosts = [];

        foreach ($items as $item) {
            $messageMetrics = $this->extractMessageMetrics($item);
            $timestamp = $messageMetrics['date'];

            $this->audienceCalculator->accumulateHourActivity($hourlyActivity, $timestamp);
            $this->accumulateTimeline(
                $timeline,
                $groupBy,
                $timestamp,
                $messageMetrics['views'],
                $messageMetrics['forwards'],
                $messageMetrics['replies'],
                $messageMetrics['reactions'],
                $messageMetrics['gifts'],
                $messageMetrics['mediaType']
            );

            $this->accumulateTotals($totals, $messageMetrics);
            $this->accumulateReactionDistribution($reactionCounts, $item);
            $this->accumulateMediaDistribution($mediaCounts, $messageMetrics['mediaType']);
            $funnelCandidates[] = [
                'views' => $messageMetrics['views'],
                'interactions' => $messageMetrics['interactions'],
                'reactions' => $messageMetrics['reactions'],
            ];

            $authorContext = $this->opinionLeadersBuilder->resolveContext($item);
            $authorKey = $authorContext['authorKey'];
            if (is_string($authorKey) && $authorKey !== '') {
                $authorIds[$authorKey] = true;
            }

            $score = $this->calculateScore(
                $messageMetrics['views'],
                $messageMetrics['forwards'],
                $messageMetrics['replies'],
                $messageMetrics['reactions'],
                $messageMetrics['gifts'],
                $weights
            );

            $this->opinionLeadersBuilder->accumulate(
                $authorStats,
                $authorDailyStats,
                $authorContext,
                $timestamp,
                $messageMetrics['forwards'],
                $messageMetrics['replies'],
                $messageMetrics['reactions'],
                $messageMetrics['gifts'],
                $weights
            );

            $topPosts[] = $this->buildTopPostRow($item, $messageMetrics, $score);
            $fraudPosts[] = [
                'id' => (int) ($item['id'] ?? 0),
                'date' => $messageMetrics['date'],
                'message' => trim((string) ($item['message'] ?? '')),
                'telegramUrl' => $item['telegramUrl'] ?? null,
                'views' => $messageMetrics['views'],
                'forwards' => $messageMetrics['forwards'],
                'replies' => $messageMetrics['replies'],
                'reactions' => $messageMetrics['reactions'],
                'gifts' => $messageMetrics['gifts'],
                'interactions' => $messageMetrics['interactions'],
            ];
        }

        ksort($timeline);
        $totals['uniqueAuthors'] = count($authorIds);

        $this->finalizeTotals($totals);
        usort($topPosts, static fn (array $left, array $right): int => $right['score'] <=> $left['score']);

        $opinionLeaders = $this->opinionLeadersBuilder->buildLeaders($authorStats);
        $opinionLeaderKeys = array_map(
            static fn (array $leader): string => (string) ($leader['authorKey'] ?? ''),
            $opinionLeaders
        );

        $timelineValues = array_values($timeline);
        $funnel = $this->funnelCalculator->build($funnelCandidates);
        $audience = $this->audienceCalculator->build($authorStats, (int) $totals['messages'], $hourlyActivity);
        $fraudSignals = $this->fraudCalculator->build($fraudPosts, $timelineValues, $totals, $audience);

        return [
            'totals' => $totals,
            'funnel' => $funnel,
            'audience' => $audience,
            'fraudSignals' => $fraudSignals,
            'timeline' => $timelineValues,
            'topMedia' => $this->buildDistribution($mediaCounts),
            'topReactions' => $this->buildDistribution($reactionCounts),
            'topPosts' => array_slice($topPosts, 0, $this->topPostsLimit()),
            'opinionLeaders' => $opinionLeaders,
            'opinionLeadersDaily' => $this->opinionLeadersBuilder->buildLeadersDaily($authorDailyStats, $opinionLeaderKeys),
            'chatUsername' => $chatUsername,
        ];
    }

    /**
     * @return array{
     *     date: int,
     *     views: int,
     *     forwards: int,
     *     replies: int,
     *     reactions: int,
     *     gifts: int,
     *     mediaType: string,
     *     interactions: int
     * }
     */
    private function extractMessageMetrics(array $item): array
    {
        $reactions = 0;
        foreach ($item['reactions'] ?? [] as $reaction) {
            $reactions += max(0, (int) ($reaction['count'] ?? 0));
        }

        $forwards = max(0, (int) ($item['forwards'] ?? 0));
        $replies = max(0, (int) ($item['repliesCount'] ?? 0));
        $gifts = !empty($item['gifts']['hasGift']) ? 1 : 0;

        return [
            'date' => (int) ($item['date'] ?? 0),
            'views' => max(0, (int) ($item['views'] ?? 0)),
            'forwards' => $forwards,
            'replies' => $replies,
            'reactions' => $reactions,
            'gifts' => $gifts,
            'mediaType' => (string) ($item['media']['type'] ?? 'none'),
            'interactions' => $forwards + $replies + $reactions + $gifts,
        ];
    }

    /**
     * @return array{
     *     messages: int,
     *     views: int,
     *     forwards: int,
     *     replies: int,
     *     reactions: int,
     *     gifts: int,
     *     mediaPosts: int,
     *     uniqueAuthors: int,
     *     avgViewsPerPost: float,
     *     avgInteractionsPerPost: float
     * }
     */
    private function initialTotals(): array
    {
        return [
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
    }

    /**
     * @param array<string, mixed> $totals
     * @param array{
     *     views: int,
     *     forwards: int,
     *     replies: int,
     *     reactions: int,
     *     gifts: int,
     *     mediaType: string
     * } $messageMetrics
     */
    private function accumulateTotals(array &$totals, array $messageMetrics): void
    {
        $totals['messages']++;
        $totals['views'] += $messageMetrics['views'];
        $totals['forwards'] += $messageMetrics['forwards'];
        $totals['replies'] += $messageMetrics['replies'];
        $totals['reactions'] += $messageMetrics['reactions'];
        $totals['gifts'] += $messageMetrics['gifts'];
        $totals['mediaPosts'] += $messageMetrics['mediaType'] !== 'none' ? 1 : 0;
    }

    /**
     * @param array<string, mixed> $totals
     */
    private function finalizeTotals(array &$totals): void
    {
        if ((int) $totals['messages'] <= 0) {
            return;
        }

        $messages = (int) $totals['messages'];
        $totals['avgViewsPerPost'] = round(((int) $totals['views']) / $messages, 2);
        $totals['avgInteractionsPerPost'] = round(
            (((int) $totals['forwards']) + ((int) $totals['replies']) + ((int) $totals['reactions']) + ((int) $totals['gifts'])) / $messages,
            2
        );
    }

    /**
     * @param array<string, int> $mediaCounts
     */
    private function accumulateMediaDistribution(array &$mediaCounts, string $mediaType): void
    {
        if ($mediaType === 'none') {
            return;
        }

        $mediaCounts[$mediaType] = ($mediaCounts[$mediaType] ?? 0) + 1;
    }

    /**
     * @param array<string, int> $reactionCounts
     * @param array<string, mixed> $item
     */
    private function accumulateReactionDistribution(array &$reactionCounts, array $item): void
    {
        foreach ($item['reactions'] ?? [] as $reaction) {
            $count = max(0, (int) ($reaction['count'] ?? 0));
            $reactionLabel = trim((string) ($reaction['emoji'] ?? $reaction['key'] ?? 'Reaction'));

            if ($reactionLabel === '') {
                $reactionLabel = 'Reaction';
            }

            $reactionCounts[$reactionLabel] = ($reactionCounts[$reactionLabel] ?? 0) + $count;
        }
    }

    /**
     * @param array<string, array<string, mixed>> $timeline
     */
    private function accumulateTimeline(
        array &$timeline,
        string $groupBy,
        int $timestamp,
        int $views,
        int $forwards,
        int $replies,
        int $reactions,
        int $gifts,
        string $mediaType
    ): void {
        $bucketKey = $this->bucketKey($timestamp, $groupBy);
        if ($bucketKey === null || !isset($timeline[$bucketKey])) {
            return;
        }

        $timeline[$bucketKey]['messages']++;
        $timeline[$bucketKey]['views'] += $views;
        $timeline[$bucketKey]['forwards'] += $forwards;
        $timeline[$bucketKey]['replies'] += $replies;
        $timeline[$bucketKey]['reactions'] += $reactions;
        $timeline[$bucketKey]['gifts'] += $gifts;
        $timeline[$bucketKey]['media'] += $mediaType !== 'none' ? 1 : 0;
        $timeline[$bucketKey]['interactions'] += $forwards + $replies + $reactions + $gifts;
    }

    /**
     * @param array{views: float, forwards: float, replies: float, reactions: float, gifts: float} $weights
     */
    private function calculateScore(
        int $views,
        int $forwards,
        int $replies,
        int $reactions,
        int $gifts,
        array $weights
    ): float {
        return ($views * $weights['views'])
            + ($forwards * $weights['forwards'])
            + ($replies * $weights['replies'])
            + ($reactions * $weights['reactions'])
            + ($gifts * $weights['gifts']);
    }

    /**
     * @param array<string, mixed> $item
     * @param array{
     *     date: int,
     *     views: int,
     *     forwards: int,
     *     replies: int,
     *     reactions: int
     * } $messageMetrics
     * @return array<string, mixed>
     */
    private function buildTopPostRow(array $item, array $messageMetrics, float $score): array
    {
        return [
            'id' => (int) ($item['id'] ?? 0),
            'date' => $messageMetrics['date'],
            'message' => Str::limit(trim((string) ($item['message'] ?? '')), 180),
            'telegramUrl' => $item['telegramUrl'] ?? null,
            'views' => $messageMetrics['views'],
            'forwards' => $messageMetrics['forwards'],
            'replies' => $messageMetrics['replies'],
            'reactions' => $messageMetrics['reactions'],
            'mediaLabel' => $item['media']['label'] ?? null,
            'score' => round($score, 2),
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

        foreach (array_slice($counts, 0, $this->topDistributionLimit(), true) as $label => $count) {
            $distribution[] = [
                'key' => $label,
                'label' => $label,
                'count' => $count,
                'share' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ];
        }

        return $distribution;
    }

    private function bucketKey(int $timestamp, string $groupBy): ?string
    {
        if ($groupBy !== 'hour' && $groupBy !== 'day') {
            return null;
        }

        return $groupBy === 'hour'
            ? Carbon::createFromTimestamp($timestamp, config('app.timezone'))->format('Y-m-d H:00')
            : Carbon::createFromTimestamp($timestamp, config('app.timezone'))->format('Y-m-d');
    }

    private function topPostsLimit(): int
    {
        return max(1, (int) config('osint.telegram.analytics.top_posts_limit', 5));
    }

    private function topDistributionLimit(): int
    {
        return max(1, (int) config('osint.telegram.analytics.top_distribution_limit', 5));
    }
}
