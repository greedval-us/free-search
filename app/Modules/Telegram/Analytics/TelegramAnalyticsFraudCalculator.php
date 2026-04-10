<?php

namespace App\Modules\Telegram\Analytics;

use Illuminate\Support\Str;

class TelegramAnalyticsFraudCalculator
{
    /**
     * @param array<int, array{
     *     id: int,
     *     date: int,
     *     message: string,
     *     telegramUrl: string|null,
     *     views: int,
     *     forwards: int,
     *     replies: int,
     *     reactions: int,
     *     gifts: int,
     *     interactions: int
     * }> $posts
     * @param array<int, array<string, mixed>> $timeline
     * @param array<string, mixed> $totals
     * @param array<string, mixed> $audience
     * @return array<string, mixed>
     */
    public function build(array $posts, array $timeline, array $totals, array $audience): array
    {
        $riskScore = 0;
        $triggers = [];
        $suspiciousPosts = [];

        foreach ($posts as $post) {
            $postRisk = 0;
            $reasons = [];
            $views = (int) ($post['views'] ?? 0);
            $forwards = (int) ($post['forwards'] ?? 0);
            $reactions = (int) ($post['reactions'] ?? 0);
            $interactions = (int) ($post['interactions'] ?? 0);
            $gifts = (int) ($post['gifts'] ?? 0);

            if ($views === 0 && $interactions >= 5) {
                $postRisk += 35;
                $reasons[] = 'interactions_without_views';
            }

            if ($views > 0 && $reactions >= 20 && ($reactions / max(1, $views)) >= 0.7) {
                $postRisk += 30;
                $reasons[] = 'high_reaction_ratio';
            }

            if ($views > 0 && $forwards >= 15 && ($forwards / max(1, $views)) >= 0.5) {
                $postRisk += 25;
                $reasons[] = 'high_forward_ratio';
            }

            if ($gifts > 0 && $views < 10) {
                $postRisk += 20;
                $reasons[] = 'gifts_with_low_views';
            }

            if ($interactions >= 50 && $views < 30) {
                $postRisk += 10;
                $reasons[] = 'high_interactions_low_views';
            }

            if ($postRisk >= 30) {
                $suspiciousPosts[] = [
                    'id' => (int) ($post['id'] ?? 0),
                    'date' => (int) ($post['date'] ?? 0),
                    'message' => Str::limit(trim((string) ($post['message'] ?? '')), 160),
                    'telegramUrl' => $post['telegramUrl'] ?? null,
                    'riskScore' => $postRisk,
                    'reasons' => $reasons,
                ];
            }
        }

        usort($suspiciousPosts, static fn (array $left, array $right): int => ($right['riskScore'] ?? 0) <=> ($left['riskScore'] ?? 0));

        $messages = max(1, (int) ($totals['messages'] ?? 0));
        $zeroViewWithInteractions = count(array_filter(
            $posts,
            static fn (array $post): bool => ((int) ($post['views'] ?? 0)) === 0 && ((int) ($post['interactions'] ?? 0)) > 0
        ));

        if ($zeroViewWithInteractions >= 3) {
            $riskScore += 30;
            $triggers[] = [
                'key' => 'zero_view_interactions',
                'score' => 30,
                'value' => $zeroViewWithInteractions,
                'threshold' => 3,
            ];
        }

        $topAuthorShare = (float) ($audience['topAuthorShare'] ?? 0.0);
        if ($messages >= 20 && $topAuthorShare >= 60.0) {
            $riskScore += 20;
            $triggers[] = [
                'key' => 'author_concentration',
                'score' => 20,
                'value' => $topAuthorShare,
                'threshold' => 60,
            ];
        }

        $maxBucketMessages = 0;
        foreach ($timeline as $bucket) {
            $maxBucketMessages = max($maxBucketMessages, (int) ($bucket['messages'] ?? 0));
        }

        $burstShare = $messages > 0 ? round(($maxBucketMessages / $messages) * 100, 1) : 0.0;
        if ($messages >= 20 && $burstShare >= 45.0) {
            $riskScore += 20;
            $triggers[] = [
                'key' => 'time_burst',
                'score' => 20,
                'value' => $burstShare,
                'threshold' => 45,
            ];
        }

        $highRatioReactions = count(array_filter(
            $posts,
            static fn (array $post): bool =>
                ((int) ($post['views'] ?? 0)) >= 20
                && ((int) ($post['reactions'] ?? 0)) >= 10
                && (((int) ($post['reactions'] ?? 0)) / max(1, (int) ($post['views'] ?? 0))) >= 0.5
        ));

        if ($highRatioReactions >= 3) {
            $riskScore += 15;
            $triggers[] = [
                'key' => 'reaction_ratio_cluster',
                'score' => 15,
                'value' => $highRatioReactions,
                'threshold' => 3,
            ];
        }

        if (count($suspiciousPosts) >= 3) {
            $riskScore += 15;
            $triggers[] = [
                'key' => 'suspicious_posts_cluster',
                'score' => 15,
                'value' => count($suspiciousPosts),
                'threshold' => 3,
            ];
        }

        $riskScore = min(100, $riskScore);
        $riskLevel = $riskScore >= 60 ? 'high' : ($riskScore >= 30 ? 'medium' : 'low');

        return [
            'riskLevel' => $riskLevel,
            'riskScore' => $riskScore,
            'triggers' => $triggers,
            'suspiciousPosts' => array_slice($suspiciousPosts, 0, 5),
            'suspiciousPostsCount' => count($suspiciousPosts),
        ];
    }
}
