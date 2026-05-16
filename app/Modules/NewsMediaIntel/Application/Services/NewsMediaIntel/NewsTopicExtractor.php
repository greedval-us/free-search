<?php

namespace App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel;

use App\Modules\NewsMediaIntel\Domain\DTO\NewsMentionDTO;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsTopicDTO;

final class NewsTopicExtractor
{
    /**
     * @param array<int, NewsMentionDTO> $mentions
     * @return array<int, NewsTopicDTO>
     */
    public function extract(array $mentions): array
    {
        $stopWords = ['the', 'and', 'with', 'from', 'that', 'this', 'for', 'или', 'как', 'что', 'это', 'при', 'после', 'about'];
        $bucket = [];

        foreach ($mentions as $mention) {
            $text = mb_strtolower(trim($mention->title . ' ' . $mention->snippet));
            $words = preg_split('/[^\p{L}\p{N}]+/u', $text) ?: [];

            foreach ($words as $word) {
                if (mb_strlen($word) < 4 || in_array($word, $stopWords, true)) {
                    continue;
                }

                $bucket[$word] = (int) ($bucket[$word] ?? 0) + 1;
            }
        }

        arsort($bucket);
        $topics = [];
        foreach (array_slice($bucket, 0, 20, true) as $word => $count) {
            $topics[] = new NewsTopicDTO(topic: (string) $word, count: (int) $count);
        }

        return $topics;
    }
}

