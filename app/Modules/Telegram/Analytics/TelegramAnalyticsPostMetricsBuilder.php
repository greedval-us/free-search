<?php

namespace App\Modules\Telegram\Analytics;

use Illuminate\Support\Str;

final class TelegramAnalyticsPostMetricsBuilder
{
    /**
     * @param array<string, mixed> $item
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
    public function extractMessageMetrics(array $item): array
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
     * @param array{views: float, forwards: float, replies: float, reactions: float, gifts: float} $weights
     */
    public function calculateScore(
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
    public function buildTopPostRow(array $item, array $messageMetrics, float $score): array
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
}
