<?php

namespace App\Modules\NewsMediaIntel\Application\Services\Marketing;

use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel\NewsMentionDeduplicator;
use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel\NewsMentionFingerprintFactory;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsMentionDTO;

final readonly class MarketingMentionSet
{
    public function __construct(
        private NewsMentionDeduplicator $deduplicator,
        private NewsMentionFingerprintFactory $fingerprints,
    ) {}

    /**
     * Keep aliases together before choosing the existing deduplicator's best article.
     * A content/URL bridge must also preserve the provenance of every earlier alias.
     *
     * @param  array<string, list<NewsMentionDTO>>  $categories
     */
    public function merge(array $categories): array
    {
        $items = [];
        foreach ($categories as $category => $mentions) {
            foreach ($mentions as $mention) {
                $items[] = ['mention' => $mention, 'category' => $category,
                    'linkKey' => $this->fingerprints->linkKey($mention->link),
                    'contentKey' => $this->fingerprints->contentKey($mention->title, $mention->snippet)];
            }
        }

        $sets = [];
        $remaining = array_fill_keys(array_keys($items), true);
        while ($remaining !== []) {
            $first = array_key_first($remaining);
            $members = [$first];
            unset($remaining[$first]);
            $links = $contents = [];
            do {
                $expanded = false;
                foreach ($members as $index) {
                    if ($items[$index]['linkKey'] !== '') {
                        $links[$items[$index]['linkKey']] = true;
                    }
                    if ($items[$index]['contentKey'] !== '') {
                        $contents[$items[$index]['contentKey']] = true;
                    }
                }
                foreach (array_keys($remaining) as $index) {
                    if (isset($links[$items[$index]['linkKey']]) || isset($contents[$items[$index]['contentKey']])) {
                        $members[] = $index;
                        unset($remaining[$index]);
                        $expanded = true;
                    }
                }
            } while ($expanded);

            sort($members);
            $group = array_map(static fn (int $index): NewsMentionDTO => $items[$index]['mention'], $members);
            $winner = $this->deduplicator->deduplicate($group)[0] ?? null;
            if ($winner === null) {
                continue;
            }

            $engines = $types = $generalLinks = [];
            foreach ($members as $index) {
                $mention = $items[$index]['mention'];
                $type = $items[$index]['category'];
                $types[] = $type;
                $engines = [...$engines, ...($mention->engines ?? [])];
                if ($type === 'general') {
                    $generalLinks[] = ['url' => $mention->link, 'title' => $mention->title,
                        'position' => isset($mention->position) && $mention->position > 0 ? $mention->position : null];
                }
            }
            $sets[] = ['mention' => $winner, 'categories' => array_values(array_unique($types)),
                'engines' => array_values(array_unique($engines)), 'generalLinks' => $generalLinks];
        }

        return $sets;
    }

    /** Alternate categories so a large web sample cannot exclude all news results. */
    public function limit(array $sets, int $limit): array
    {
        if (count($sets) <= $limit) {
            return $sets;
        }

        $buckets = ['general' => [], 'news' => []];
        foreach ($sets as $index => $set) {
            foreach ($set['categories'] as $category) {
                $buckets[$category][] = $index;
            }
        }

        $chosen = [];
        while (count($chosen) < $limit && ($buckets['general'] !== [] || $buckets['news'] !== [])) {
            foreach (array_keys($buckets) as $category) {
                do {
                    $index = array_shift($buckets[$category]);
                } while ($index !== null && isset($chosen[$index]));
                if ($index !== null) {
                    $chosen[$index] = $sets[$index];
                }
                if (count($chosen) >= $limit) {
                    break;
                }
            }
        }
        ksort($chosen);

        return array_values($chosen);
    }

    public static function host(string $url): string
    {
        $host = mb_strtolower((string) (parse_url($url, PHP_URL_HOST) ?: ''));

        return preg_replace('/^www\./i', '', rtrim($host, '.')) ?? $host;
    }

    public static function percent(int $count, int $total): float
    {
        return $total > 0 ? round($count / $total * 100, 2) : 0.0;
    }
}
