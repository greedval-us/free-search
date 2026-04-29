<?php

namespace App\Modules\EmailIntel\Application\Services\EmailIntel;

final class EmailDeliverabilityHintBuilder
{
    /**
     * @param array<string, mixed> $dns
     * @param array<string, mixed> $spf
     * @param array<string, mixed> $dmarc
     * @param array<string, mixed>|null $webSnapshot
     * @return array{score: int, status: string, hints: array<int, array{key: string, level: string, message: string, passed: bool}>}
     */
    public function build(array $dns, array $spf, array $dmarc, ?array $webSnapshot = null): array
    {
        $hints = [
            $this->hint('mx_ready', count($dns['mx'] ?? []) > 0, 'high', 'Domain has MX records.'),
            $this->hint('spf_present', (bool) ($spf['present'] ?? false), 'medium', 'SPF record is present.'),
            $this->hint('spf_strict', ($spf['strictness'] ?? null) === 'strict', 'medium', 'SPF uses a strict -all policy.'),
            $this->hint('dmarc_present', (bool) ($dmarc['present'] ?? false), 'medium', 'DMARC record is present.'),
            $this->hint('dmarc_enforced', in_array($dmarc['strength'] ?? null, ['strong', 'moderate'], true), 'medium', 'DMARC is enforced with quarantine or reject.'),
            $this->hint('domain_resolves', count($dns['a'] ?? []) + count($dns['aaaa'] ?? []) > 0, 'low', 'Domain resolves to A/AAAA records.'),
        ];

        if ($webSnapshot !== null) {
            $hints[] = $this->hint('web_available', (bool) ($webSnapshot['available'] ?? false), 'low', 'Domain website responds over HTTPS.');
        }

        $score = $this->score($hints);

        return [
            'score' => $score,
            'status' => $this->status($score),
            'hints' => $hints,
        ];
    }

    /**
     * @return array{key: string, level: string, message: string, passed: bool}
     */
    private function hint(string $key, bool $passed, string $level, string $message): array
    {
        return [
            'key' => $key,
            'level' => $level,
            'message' => $message,
            'passed' => $passed,
        ];
    }

    /**
     * @param array<int, array{level: string, passed: bool}> $hints
     */
    private function score(array $hints): int
    {
        $earned = 0;
        $available = 0;

        foreach ($hints as $hint) {
            $weight = match ($hint['level']) {
                'high' => 25,
                'medium' => 15,
                default => 10,
            };
            $available += $weight;
            $earned += $hint['passed'] ? $weight : 0;
        }

        return $available === 0 ? 0 : (int) round(($earned / $available) * 100);
    }

    private function status(int $score): string
    {
        return match (true) {
            $score >= 80 => 'ready',
            $score >= 50 => 'partial',
            default => 'weak',
        };
    }
}
