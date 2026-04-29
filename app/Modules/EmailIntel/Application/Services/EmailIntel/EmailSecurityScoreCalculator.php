<?php

namespace App\Modules\EmailIntel\Application\Services\EmailIntel;

final class EmailSecurityScoreCalculator
{
    /**
     * @param array<string, mixed> $dns
     * @param array<string, mixed> $spf
     * @param array<string, mixed> $dmarc
     * @param array<string, mixed> $profile
     * @return array<string, mixed>
     */
    public function calculate(array $dns, array $spf, array $dmarc, array $profile): array
    {
        $mailSecurity = 0;
        $domainHealth = 0;
        $identityConfidence = 100;

        $mailSecurity += count($dns['mx'] ?? []) > 0 ? 25 : 0;
        $mailSecurity += ($spf['present'] ?? false) ? 25 : 0;
        $mailSecurity += match ($spf['strictness'] ?? 'missing') {
            'strict' => 15,
            'soft' => 10,
            'neutral' => 4,
            'open' => -15,
            default => 0,
        };
        $mailSecurity += match ($dmarc['strength'] ?? 'missing') {
            'strong' => 35,
            'moderate' => 25,
            'monitoring' => 10,
            default => 0,
        };

        $domainHealth += count($dns['a'] ?? []) + count($dns['aaaa'] ?? []) > 0 ? 50 : 0;
        $domainHealth += count($dns['mx'] ?? []) > 0 ? 50 : 0;

        if (($profile['isDisposable'] ?? false) === true) {
            $identityConfidence -= 45;
        }

        if (($profile['isRoleAccount'] ?? false) === true) {
            $identityConfidence -= 15;
        }

        return [
            'mailSecurity' => $this->clamp($mailSecurity),
            'domainHealth' => $this->clamp($domainHealth),
            'identityConfidence' => $this->clamp($identityConfidence),
            'overall' => $this->clamp((int) round(($mailSecurity + $domainHealth + $identityConfidence) / 3)),
        ];
    }

    private function clamp(int $value): int
    {
        return max(0, min(100, $value));
    }
}
