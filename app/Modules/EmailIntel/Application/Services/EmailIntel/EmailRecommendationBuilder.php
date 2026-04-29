<?php

namespace App\Modules\EmailIntel\Application\Services\EmailIntel;

final class EmailRecommendationBuilder
{
    /**
     * @param array<int, array<string, string>> $signals
     * @param array<string, mixed> $analytics
     * @return array<int, array{key: string, priority: string, impact: int}>
     */
    public function build(array $signals, array $analytics): array
    {
        $keys = array_column($signals, 'type');
        $recommendations = [];

        if (in_array('missing_mx', $keys, true)) {
            $recommendations[] = ['key' => 'configure_mx', 'priority' => 'high', 'impact' => 25];
        }

        if (in_array('missing_spf', $keys, true) || in_array('weak_spf', $keys, true) || in_array('open_spf', $keys, true)) {
            $recommendations[] = ['key' => 'harden_spf', 'priority' => 'high', 'impact' => 20];
        }

        if (in_array('missing_dmarc', $keys, true) || in_array('monitoring_dmarc', $keys, true)) {
            $recommendations[] = ['key' => 'enforce_dmarc', 'priority' => 'high', 'impact' => 25];
        }

        if (in_array('disposable_provider', $keys, true)) {
            $recommendations[] = ['key' => 'review_disposable', 'priority' => 'medium', 'impact' => 15];
        }

        if (in_array('role_account', $keys, true)) {
            $recommendations[] = ['key' => 'verify_role_account', 'priority' => 'low', 'impact' => 8];
        }

        if (($analytics['scores']['overall'] ?? 0) >= 80 && $recommendations === []) {
            $recommendations[] = ['key' => 'maintain_posture', 'priority' => 'low', 'impact' => 0];
        }

        return $recommendations;
    }
}
