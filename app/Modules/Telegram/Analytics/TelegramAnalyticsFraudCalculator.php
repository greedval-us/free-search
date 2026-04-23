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
        $postRules = $this->postRules();

        foreach ($posts as $post) {
            $postRisk = 0;
            $reasons = [];
            $views = (int) ($post['views'] ?? 0);
            $forwards = (int) ($post['forwards'] ?? 0);
            $reactions = (int) ($post['reactions'] ?? 0);
            $interactions = (int) ($post['interactions'] ?? 0);
            $gifts = (int) ($post['gifts'] ?? 0);

            if (
                $views === 0
                && $interactions >= (int) ($postRules['interactions_without_views']['interactions_min'] ?? 5)
            ) {
                $postRisk += (int) ($postRules['interactions_without_views']['score'] ?? 35);
                $reasons[] = 'interactions_without_views';
            }

            if (
                $views > 0
                && $reactions >= (int) ($postRules['high_reaction_ratio']['reactions_min'] ?? 20)
                && ($reactions / max(1, $views)) >= (float) ($postRules['high_reaction_ratio']['ratio_min'] ?? 0.7)
            ) {
                $postRisk += (int) ($postRules['high_reaction_ratio']['score'] ?? 30);
                $reasons[] = 'high_reaction_ratio';
            }

            if (
                $views > 0
                && $forwards >= (int) ($postRules['high_forward_ratio']['forwards_min'] ?? 15)
                && ($forwards / max(1, $views)) >= (float) ($postRules['high_forward_ratio']['ratio_min'] ?? 0.5)
            ) {
                $postRisk += (int) ($postRules['high_forward_ratio']['score'] ?? 25);
                $reasons[] = 'high_forward_ratio';
            }

            if (
                $gifts > 0
                && $views < (int) ($postRules['gifts_with_low_views']['views_max_exclusive'] ?? 10)
            ) {
                $postRisk += (int) ($postRules['gifts_with_low_views']['score'] ?? 20);
                $reasons[] = 'gifts_with_low_views';
            }

            if (
                $interactions >= (int) ($postRules['high_interactions_low_views']['interactions_min'] ?? 50)
                && $views < (int) ($postRules['high_interactions_low_views']['views_max_exclusive'] ?? 30)
            ) {
                $postRisk += (int) ($postRules['high_interactions_low_views']['score'] ?? 10);
                $reasons[] = 'high_interactions_low_views';
            }

            if ($postRisk >= (int) ($postRules['suspicious_post_min_risk_score'] ?? 30)) {
                $suspiciousPosts[] = [
                    'id' => (int) ($post['id'] ?? 0),
                    'date' => (int) ($post['date'] ?? 0),
                    'message' => Str::limit(trim((string) ($post['message'] ?? '')), $this->suspiciousPostExcerptLength()),
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

        $triggerRules = $this->triggerRules();
        $zeroViewInteractions = $triggerRules['zero_view_interactions'] ?? [];
        if ($zeroViewWithInteractions >= (int) ($zeroViewInteractions['threshold'] ?? 3)) {
            $riskScore += (int) ($zeroViewInteractions['score'] ?? 30);
            $triggers[] = [
                'key' => 'zero_view_interactions',
                'score' => (int) ($zeroViewInteractions['score'] ?? 30),
                'value' => $zeroViewWithInteractions,
                'threshold' => (int) ($zeroViewInteractions['threshold'] ?? 3),
            ];
        }

        $topAuthorShare = (float) ($audience['topAuthorShare'] ?? 0.0);
        $authorConcentration = $triggerRules['author_concentration'] ?? [];
        if (
            $messages >= (int) ($authorConcentration['messages_min'] ?? 20)
            && $topAuthorShare >= (float) ($authorConcentration['top_author_share_min'] ?? 60.0)
        ) {
            $riskScore += (int) ($authorConcentration['score'] ?? 20);
            $triggers[] = [
                'key' => 'author_concentration',
                'score' => (int) ($authorConcentration['score'] ?? 20),
                'value' => $topAuthorShare,
                'threshold' => (float) ($authorConcentration['top_author_share_min'] ?? 60.0),
            ];
        }

        $maxBucketMessages = 0;
        foreach ($timeline as $bucket) {
            $maxBucketMessages = max($maxBucketMessages, (int) ($bucket['messages'] ?? 0));
        }

        $burstShare = $messages > 0 ? round(($maxBucketMessages / $messages) * 100, 1) : 0.0;
        $timeBurst = $triggerRules['time_burst'] ?? [];
        if (
            $messages >= (int) ($timeBurst['messages_min'] ?? 20)
            && $burstShare >= (float) ($timeBurst['burst_share_min'] ?? 45.0)
        ) {
            $riskScore += (int) ($timeBurst['score'] ?? 20);
            $triggers[] = [
                'key' => 'time_burst',
                'score' => (int) ($timeBurst['score'] ?? 20),
                'value' => $burstShare,
                'threshold' => (float) ($timeBurst['burst_share_min'] ?? 45.0),
            ];
        }

        $reactionRatioCluster = $triggerRules['reaction_ratio_cluster'] ?? [];
        $highRatioReactions = count(array_filter(
            $posts,
            static fn (array $post): bool =>
                ((int) ($post['views'] ?? 0)) >= (int) ($reactionRatioCluster['views_min'] ?? 20)
                && ((int) ($post['reactions'] ?? 0)) >= (int) ($reactionRatioCluster['reactions_min'] ?? 10)
                && (((int) ($post['reactions'] ?? 0)) / max(1, (int) ($post['views'] ?? 0))) >= (float) ($reactionRatioCluster['ratio_min'] ?? 0.5)
        ));

        if ($highRatioReactions >= (int) ($reactionRatioCluster['threshold'] ?? 3)) {
            $riskScore += (int) ($reactionRatioCluster['score'] ?? 15);
            $triggers[] = [
                'key' => 'reaction_ratio_cluster',
                'score' => (int) ($reactionRatioCluster['score'] ?? 15),
                'value' => $highRatioReactions,
                'threshold' => (int) ($reactionRatioCluster['threshold'] ?? 3),
            ];
        }

        $suspiciousPostsCluster = $triggerRules['suspicious_posts_cluster'] ?? [];
        if (count($suspiciousPosts) >= (int) ($suspiciousPostsCluster['threshold'] ?? 3)) {
            $riskScore += (int) ($suspiciousPostsCluster['score'] ?? 15);
            $triggers[] = [
                'key' => 'suspicious_posts_cluster',
                'score' => (int) ($suspiciousPostsCluster['score'] ?? 15),
                'value' => count($suspiciousPosts),
                'threshold' => (int) ($suspiciousPostsCluster['threshold'] ?? 3),
            ];
        }

        $riskScore = min($this->riskMaxScore(), $riskScore);
        $riskLevel = $riskScore >= $this->riskHighThreshold()
            ? 'high'
            : ($riskScore >= $this->riskMediumThreshold() ? 'medium' : 'low');

        return [
            'riskLevel' => $riskLevel,
            'riskScore' => $riskScore,
            'triggers' => $triggers,
            'suspiciousPosts' => array_slice($suspiciousPosts, 0, $this->suspiciousPostsLimit()),
            'suspiciousPostsCount' => count($suspiciousPosts),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function postRules(): array
    {
        $rules = config('osint.telegram.analytics.fraud.post_rules', []);

        return is_array($rules) ? $rules : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function triggerRules(): array
    {
        $rules = config('osint.telegram.analytics.fraud.triggers', []);

        return is_array($rules) ? $rules : [];
    }

    private function suspiciousPostExcerptLength(): int
    {
        return max(1, (int) config('osint.telegram.analytics.fraud.suspicious_post_excerpt_length', 160));
    }

    private function suspiciousPostsLimit(): int
    {
        return max(1, (int) config('osint.telegram.analytics.fraud.suspicious_posts_limit', 5));
    }

    private function riskMaxScore(): int
    {
        return max(1, (int) config('osint.telegram.analytics.fraud.risk_max_score', 100));
    }

    private function riskMediumThreshold(): int
    {
        return max(0, (int) config('osint.telegram.analytics.fraud.risk_medium_threshold', 30));
    }

    private function riskHighThreshold(): int
    {
        return max($this->riskMediumThreshold(), (int) config('osint.telegram.analytics.fraud.risk_high_threshold', 60));
    }
}
