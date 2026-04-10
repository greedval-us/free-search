<?php

namespace App\Modules\Telegram\Analytics;

use Carbon\Carbon;

class TelegramAnalyticsOpinionLeadersBuilder
{
    /**
     * @param array<string, mixed> $item
     * @return array{authorKey: string|null, authorId: int|null, authorLabel: string|null}
     */
    public function resolveContext(array $item): array
    {
        $authorId = $item['authorId'] ?? null;

        return [
            'authorKey' => $this->resolveAuthorKey($item),
            'authorId' => is_int($authorId) && $authorId > 0 ? $authorId : null,
            'authorLabel' => $this->resolveAuthorLabel($item),
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $authorStats
     * @param array<string, array<string, array<string, mixed>>> $authorDailyStats
     * @param array{authorKey: string|null, authorId: int|null, authorLabel: string|null} $context
     * @param array{views: float, forwards: float, replies: float, reactions: float, gifts: float} $weights
     */
    public function accumulate(
        array &$authorStats,
        array &$authorDailyStats,
        array $context,
        int $timestamp,
        int $forwards,
        int $replies,
        int $reactions,
        int $gifts,
        array $weights
    ): void {
        $authorKey = $context['authorKey'] ?? null;
        if (!is_string($authorKey) || $authorKey === '') {
            return;
        }

        if (!isset($authorStats[$authorKey])) {
            $authorStats[$authorKey] = [
                'authorKey' => $authorKey,
                'authorId' => $context['authorId'],
                'authorLabel' => $context['authorLabel'],
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
                'authorId' => $context['authorId'],
                'authorLabel' => $context['authorLabel'],
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
     * @param array<string, array<string, mixed>> $authorStats
     * @return array<int, array<string, mixed>>
     */
    public function buildLeaders(array $authorStats): array
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
    public function buildLeadersDaily(array $authorDailyStats, array $leaderKeys): array
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
}
