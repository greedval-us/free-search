<?php

namespace App\Modules\Telegram\Analytics;

use Carbon\Carbon;
use Illuminate\Support\Str;

class TelegramAnalyticsSummaryBuilder
{
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

        foreach ($items as $item) {
            $messageMetrics = $this->extractMessageMetrics($item);
            $timestamp = $messageMetrics['date'];

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

            $authorKey = $this->resolveAuthorKey($item);
            $authorLabel = $this->resolveAuthorLabel($item);
            $authorId = $item['authorId'] ?? null;

            if ($authorKey !== null) {
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

            if ($authorKey !== null) {
                $this->accumulateAuthorStats(
                    $authorStats,
                    $authorDailyStats,
                    $authorKey,
                    is_int($authorId) && $authorId > 0 ? $authorId : null,
                    $authorLabel,
                    $timestamp,
                    $messageMetrics['forwards'],
                    $messageMetrics['replies'],
                    $messageMetrics['reactions'],
                    $messageMetrics['gifts'],
                    $weights
                );
            }

            $topPosts[] = $this->buildTopPostRow($item, $messageMetrics, $score);
        }

        ksort($timeline);
        $totals['uniqueAuthors'] = count($authorIds);

        $this->finalizeTotals($totals);
        usort($topPosts, static fn (array $left, array $right): int => $right['score'] <=> $left['score']);

        $opinionLeaders = $this->buildOpinionLeaders($authorStats);
        $opinionLeaderKeys = array_map(
            static fn (array $leader): string => (string) ($leader['authorKey'] ?? ''),
            $opinionLeaders
        );

        return [
            'totals' => $totals,
            'timeline' => array_values($timeline),
            'topMedia' => $this->buildDistribution($mediaCounts),
            'topReactions' => $this->buildDistribution($reactionCounts),
            'topPosts' => array_slice($topPosts, 0, 5),
            'opinionLeaders' => $opinionLeaders,
            'opinionLeadersDaily' => $this->buildOpinionLeadersDaily($authorDailyStats, $opinionLeaderKeys),
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
     * @param array<string, array<string, mixed>> $authorStats
     * @param array<string, array<string, array<string, mixed>>> $authorDailyStats
     */
    private function accumulateAuthorStats(
        array &$authorStats,
        array &$authorDailyStats,
        string $authorKey,
        ?int $authorId,
        ?string $authorLabel,
        int $timestamp,
        int $forwards,
        int $replies,
        int $reactions,
        int $gifts,
        array $weights
    ): void {
        if (!isset($authorStats[$authorKey])) {
            $authorStats[$authorKey] = [
                'authorKey' => $authorKey,
                'authorId' => $authorId,
                'authorLabel' => $authorLabel,
                'messages' => 0,
                'forwards' => 0,
                'replies' => 0,
                'reactions' => 0,
                'gifts' => 0,
                'interactions' => 0,
                'score' => 0.0,
            ];
        }

        $leaderScore = $this->calculateScore(0, $forwards, $replies, $reactions, $gifts, $weights);
        $interactions = $forwards + $replies + $reactions + $gifts;

        $authorStats[$authorKey]['messages']++;
        $authorStats[$authorKey]['forwards'] += $forwards;
        $authorStats[$authorKey]['replies'] += $replies;
        $authorStats[$authorKey]['reactions'] += $reactions;
        $authorStats[$authorKey]['gifts'] += $gifts;
        $authorStats[$authorKey]['interactions'] += $interactions;
        $authorStats[$authorKey]['score'] += $leaderScore;

        $day = Carbon::createFromTimestamp($timestamp, config('app.timezone'));
        $dayKey = $day->format('Y-m-d');

        if (!isset($authorDailyStats[$authorKey][$dayKey])) {
            $authorDailyStats[$authorKey][$dayKey] = [
                'authorKey' => $authorKey,
                'authorId' => $authorId,
                'authorLabel' => $authorLabel,
                'dayKey' => $dayKey,
                'dayLabel' => $day->format('d.m'),
                'messages' => 0,
                'forwards' => 0,
                'replies' => 0,
                'reactions' => 0,
                'gifts' => 0,
                'interactions' => 0,
                'score' => 0.0,
            ];
        }

        $authorDailyStats[$authorKey][$dayKey]['messages']++;
        $authorDailyStats[$authorKey][$dayKey]['forwards'] += $forwards;
        $authorDailyStats[$authorKey][$dayKey]['replies'] += $replies;
        $authorDailyStats[$authorKey][$dayKey]['reactions'] += $reactions;
        $authorDailyStats[$authorKey][$dayKey]['gifts'] += $gifts;
        $authorDailyStats[$authorKey][$dayKey]['interactions'] += $interactions;
        $authorDailyStats[$authorKey][$dayKey]['score'] += $leaderScore;
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

    private function bucketKey(int $timestamp, string $groupBy): ?string
    {
        if ($groupBy !== 'hour' && $groupBy !== 'day') {
            return null;
        }

        return $groupBy === 'hour'
            ? Carbon::createFromTimestamp($timestamp, config('app.timezone'))->format('Y-m-d H:00')
            : Carbon::createFromTimestamp($timestamp, config('app.timezone'))->format('Y-m-d');
    }

    /**
     * @param array<string, mixed> $item
     */
    private function resolveAuthorKey(array $item): ?string
    {
        $authorId = $item['authorId'] ?? null;
        if (is_int($authorId) && $authorId > 0) {
            return 'id:' . $authorId;
        }

        $signature = trim((string) ($item['authorSignature'] ?? ''));
        if ($signature !== '') {
            return 'signature:' . mb_strtolower($signature);
        }

        $postAuthor = trim((string) ($item['postAuthor'] ?? ''));
        if ($postAuthor !== '') {
            return 'post_author:' . mb_strtolower($postAuthor);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function resolveAuthorLabel(array $item): ?string
    {
        $signature = trim((string) ($item['authorSignature'] ?? ''));
        if ($signature !== '') {
            return $signature;
        }

        $postAuthor = trim((string) ($item['postAuthor'] ?? ''));
        if ($postAuthor !== '') {
            return $postAuthor;
        }

        $authorId = $item['authorId'] ?? null;

        return is_int($authorId) && $authorId > 0 ? 'ID ' . $authorId : null;
    }

    /**
     * @param array<string, array<string, mixed>> $authorStats
     * @return array<int, array<string, mixed>>
     */
    private function buildOpinionLeaders(array $authorStats): array
    {
        if (count($authorStats) <= 1) {
            return [];
        }

        $leaders = array_values(array_map(static function (array $stat): array {
            $stat['score'] = round((float) ($stat['score'] ?? 0.0), 2);

            return $stat;
        }, $authorStats));

        usort($leaders, static fn (array $left, array $right): int => ($right['score'] ?? 0) <=> ($left['score'] ?? 0));

        return array_slice($leaders, 0, 8);
    }

    /**
     * @param array<string, array<string, array<string, mixed>>> $authorDailyStats
     * @param array<int, string> $leaderKeys
     * @return array<int, array<string, mixed>>
     */
    private function buildOpinionLeadersDaily(array $authorDailyStats, array $leaderKeys): array
    {
        if (count($leaderKeys) <= 1) {
            return [];
        }

        $daily = [];

        foreach ($leaderKeys as $leaderKey) {
            if (!isset($authorDailyStats[$leaderKey]) || !is_array($authorDailyStats[$leaderKey])) {
                continue;
            }

            foreach ($authorDailyStats[$leaderKey] as $dayStats) {
                if (!is_array($dayStats)) {
                    continue;
                }

                $dayStats['score'] = round((float) ($dayStats['score'] ?? 0.0), 2);
                $daily[] = $dayStats;
            }
        }

        usort($daily, static function (array $left, array $right): int {
            $leftDay = (string) ($left['dayKey'] ?? '');
            $rightDay = (string) ($right['dayKey'] ?? '');

            if ($leftDay === $rightDay) {
                return ($right['score'] ?? 0) <=> ($left['score'] ?? 0);
            }

            return $leftDay <=> $rightDay;
        });

        return $daily;
    }
}
