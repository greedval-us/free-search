<?php

namespace App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel;

use App\Modules\NewsMediaIntel\Domain\DTO\NewsMentionDTO;

final class NewsMentionDeduplicator
{
    /**
     * @param array<int, NewsMentionDTO> $mentions
     * @return array<int, NewsMentionDTO>
     */
    public function deduplicate(array $mentions): array
    {
        $map = [];

        foreach ($mentions as $mention) {
            $key = mb_strtolower(trim($mention->link));
            if ($key === '') {
                continue;
            }

            if (isset($map[$key])) {
                $existing = $map[$key];
                if ($this->shouldReplace($existing, $mention)) {
                    $map[$key] = $mention;
                }

                continue;
            }

            $map[$key] = $mention;
        }

        return array_values($map);
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
