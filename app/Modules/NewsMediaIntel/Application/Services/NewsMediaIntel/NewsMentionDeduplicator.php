<?php

namespace App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel;

use App\Modules\NewsMediaIntel\Domain\DTO\NewsMentionDTO;

final class NewsMentionDeduplicator
{
    public function __construct(
        private readonly NewsMentionFingerprintFactory $fingerprints,
    ) {
    }

    /**
     * @param array<int, NewsMentionDTO> $mentions
     * @return array<int, NewsMentionDTO>
     */
    public function deduplicate(array $mentions): array
    {
        $linkMap = [];
        $contentMap = [];

        foreach ($mentions as $mention) {
            $linkKey = $this->fingerprints->linkKey($mention->link);
            $contentKey = $this->fingerprints->contentKey($mention->title, $mention->snippet);

            if ($linkKey !== '' && isset($linkMap[$linkKey])) {
                $existing = $linkMap[$linkKey];
                if ($this->shouldReplace($existing, $mention)) {
                    $linkMap[$linkKey] = $mention;

                    if ($contentKey !== '') {
                        $contentMap[$contentKey] = $mention;
                    }
                }

                continue;
            }

            if ($contentKey !== '' && isset($contentMap[$contentKey])) {
                $existing = $contentMap[$contentKey];
                if ($this->shouldReplace($existing, $mention)) {
                    $contentMap[$contentKey] = $mention;

                    if ($linkKey !== '') {
                        $linkMap[$linkKey] = $mention;
                    }
                }

                continue;
            }

            if ($linkKey !== '') {
                $linkMap[$linkKey] = $mention;
            }

            if ($contentKey !== '') {
                $contentMap[$contentKey] = $mention;
            }
        }

        $merged = [];
        foreach (array_merge(array_values($linkMap), array_values($contentMap)) as $mention) {
            $key = spl_object_hash($mention);
            $merged[$key] = $mention;
        }

        return array_values($merged);
    }

    private function shouldReplace(NewsMentionDTO $existing, NewsMentionDTO $candidate): bool
    {
        $existingHasValidDate = $this->hasValidPublishedAt($existing->publishedAt);
        $candidateHasValidDate = $this->hasValidPublishedAt($candidate->publishedAt);

        if (!$existingHasValidDate && $candidateHasValidDate) {
            return true;
        }

        if ($existingHasValidDate !== $candidateHasValidDate) {
            return false;
        }

        return mb_strlen(trim($candidate->snippet)) > mb_strlen(trim($existing->snippet));
    }

    private function hasValidPublishedAt(string $value): bool
    {
        $raw = trim($value);
        if ($raw === '') {
            return false;
        }

        $timestamp = strtotime($raw);
        if ($timestamp === false) {
            return false;
        }

        return date('Y-m-d', $timestamp) !== '1970-01-01';
    }
}
