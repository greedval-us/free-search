<?php

namespace App\Modules\DocumentIntel\Application\Services\DocumentIntel;

final class DocumentRiskScorer
{
    /**
     * @param array<string, mixed> $document
     * @return array{score: int, level: string, reasons: array<int, string>}
     */
    public function score(array $document): array
    {
        $score = 0;
        $reasons = [];

        if (($document['error'] ?? null) !== null) {
            $score += 10;
            $reasons[] = 'doc_access_issue';
        }

        $author = (string) ($document['author'] ?? '');
        if ($author !== '') {
            $score += (int) config('osint.document_intel.risk.weights.author_exposed', 20);
            $reasons[] = 'author_exposed';
        }

        $emails = is_array($document['artifacts']['emails'] ?? null) ? $document['artifacts']['emails'] : [];
        if (count($emails) > 0) {
            $score += (int) config('osint.document_intel.risk.weights.email_exposed', 15);
            $reasons[] = 'emails_exposed';
        }

        $paths = is_array($document['artifacts']['paths'] ?? null) ? $document['artifacts']['paths'] : [];
        if (count($paths) > 0) {
            $score += (int) config('osint.document_intel.risk.weights.internal_paths_exposed', 25);
            $reasons[] = 'internal_paths_exposed';
        }

        $software = strtolower((string) ($document['software'] ?? ''));
        if ($software !== '' && (str_contains($software, 'office 2007') || str_contains($software, 'office 2010'))) {
            $score += (int) config('osint.document_intel.risk.weights.legacy_software_hint', 15);
            $reasons[] = 'legacy_software_hint';
        }

        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'level' => $this->resolveLevel($score),
            'reasons' => array_values(array_unique($reasons)),
        ];
    }

    private function resolveLevel(int $score): string
    {
        $high = (int) config('osint.document_intel.risk.thresholds.high', 60);
        $medium = (int) config('osint.document_intel.risk.thresholds.medium', 30);

        return match (true) {
            $score >= $high => 'high',
            $score >= $medium => 'medium',
            default => 'low',
        };
    }

    /**
     * @param array<int, array<string, mixed>> $documents
     * @return array{averageScore: int, level: string, topReasons: array<int, array{key: string, count: int}>}
     */
    public function aggregate(array $documents): array
    {
        if ($documents === []) {
            return [
                'averageScore' => 0,
                'level' => 'low',
                'topReasons' => [],
            ];
        }

        $sum = 0;
        $reasons = [];

        foreach ($documents as $document) {
            $score = (int) ($document['risk']['score'] ?? 0);
            $sum += $score;

            foreach (($document['risk']['reasons'] ?? []) as $reason) {
                if (!is_string($reason)) {
                    continue;
                }

                $reasons[$reason] = ($reasons[$reason] ?? 0) + 1;
            }
        }

        arsort($reasons);
        $topReasons = [];
        foreach (array_slice($reasons, 0, 5, true) as $key => $count) {
            $topReasons[] = ['key' => (string) $key, 'count' => (int) $count];
        }

        $avg = (int) round($sum / max(1, count($documents)));

        return [
            'averageScore' => $avg,
            'level' => $this->resolveLevel($avg),
            'topReasons' => $topReasons,
        ];
    }
}
