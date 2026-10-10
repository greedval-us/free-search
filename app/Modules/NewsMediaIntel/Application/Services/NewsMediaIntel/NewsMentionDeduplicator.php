<?php

namespace App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel;

use App\Modules\NewsMediaIntel\Domain\DTO\NewsMentionDTO;

final class NewsMentionDeduplicator
{
    public function __construct(
        private readonly NewsMentionFingerprintFactory $fingerprints,
    ) {}

    /**
     * @param  array<int, NewsMentionDTO>  $mentions
     * @return array<int, NewsMentionDTO>
     */
    public function deduplicate(array $mentions, bool $byContent = true): array
    {
        $linkMap = [];
        $contentMap = [];
        $groups = [];
        $nextIndex = 0;

        foreach ($mentions as $mention) {
            $linkKey = $this->fingerprints->linkKey($mention->link);
            $contentKey = $byContent ? $this->fingerprints->contentKey($mention->title, $mention->snippet) : '';
            if ($linkKey === '' && $contentKey === '') {
                continue;
            }

            $matchingIndexes = [];
            if ($linkKey !== '' && isset($linkMap[$linkKey])) {
                $matchingIndexes[] = $linkMap[$linkKey];
            }
            if ($contentKey !== '' && isset($contentMap[$contentKey])) {
                $matchingIndexes[] = $contentMap[$contentKey];
            }
            $matchingIndexes = array_values(array_unique($matchingIndexes));
            sort($matchingIndexes);

            $index = $matchingIndexes[0] ?? $nextIndex++;
            $winner = $groups[$index] ?? $mention;
            $contributors = [$mention];
            foreach ($matchingIndexes as $matchingIndex) {
                $contributors[] = $groups[$matchingIndex];
            }
            foreach (array_slice($matchingIndexes, 1) as $matchingIndex) {
                if ($this->shouldReplace($winner, $groups[$matchingIndex])) {
                    $winner = $groups[$matchingIndex];
                }
            }
            if ($matchingIndexes !== [] && $this->shouldReplace($winner, $mention)) {
                $winner = $mention;
            }

            // A URL/content bridge joins groups; all earlier aliases must follow the same winner.
            if (count($matchingIndexes) > 1) {
                $joinedIndexes = array_fill_keys($matchingIndexes, true);
                foreach ($linkMap as $key => $mappedIndex) {
                    if (isset($joinedIndexes[$mappedIndex])) {
                        $linkMap[$key] = $index;
                    }
                }
                foreach ($contentMap as $key => $mappedIndex) {
                    if (isset($joinedIndexes[$mappedIndex])) {
                        $contentMap[$key] = $index;
                    }
                }
                foreach (array_slice($matchingIndexes, 1) as $joinedIndex) {
                    unset($groups[$joinedIndex]);
                }
            }

            $groups[$index] = $this->mergeProvenance($winner, $contributors);
            if ($linkKey !== '') {
                $linkMap[$linkKey] = $index;
            }
            if ($contentKey !== '') {
                $contentMap[$contentKey] = $index;
            }
        }

        return array_values($groups);
    }

    /** @param list<NewsMentionDTO> $contributors */
    private function mergeProvenance(NewsMentionDTO $winner, array $contributors): NewsMentionDTO
    {
        $engines = [];
        $categories = [];
        $position = null;
        $publisher = $winner->publisher;

        foreach ($contributors as $mention) {
            $engines = [...$engines, ...$mention->engines];
            $categories = [...$categories, ...($mention->categories !== [] ? $mention->categories : [$mention->category])];
            if ($mention->position !== null && ($position === null || $mention->position < $position)) {
                $position = $mention->position;
            }
            if ($publisher === '' && $mention->publisher !== '') {
                $publisher = $mention->publisher;
            }
        }

        $engines = array_values(array_unique($engines));
        $categories = array_values(array_unique(array_filter($categories, static fn (string $category): bool => $category !== '' && $category !== 'mixed')));
        sort($engines);
        sort($categories);

        return new NewsMentionDTO(
            source: $winner->source,
            title: $winner->title,
            snippet: $winner->snippet,
            link: $winner->link,
            publishedAt: $winner->publishedAt,
            engines: $engines,
            category: count($categories) > 1 ? 'mixed' : ($categories[0] ?? $winner->category),
            position: $position,
            publisher: $publisher,
            categories: $categories,
        );
    }

    private function shouldReplace(NewsMentionDTO $existing, NewsMentionDTO $candidate): bool
    {
        $existingHasValidDate = $this->hasValidPublishedAt($existing->publishedAt);
        $candidateHasValidDate = $this->hasValidPublishedAt($candidate->publishedAt);

        if (! $existingHasValidDate && $candidateHasValidDate) {
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
