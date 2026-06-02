<?php

namespace App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel;

use App\Modules\NewsMediaIntel\Application\Support\NewsMediaIntelConfig;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsMentionDTO;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsTopicDTO;

final class NewsTopicExtractor
{
    public function __construct(
        private readonly NewsMediaIntelConfig $config,
    ) {
    }

    /**
     * @param array<int, NewsMentionDTO> $mentions
     * @return array<int, NewsTopicDTO>
     */
    public function extract(array $mentions): array
    {
        $bucket = [];

        foreach ($mentions as $mention) {
            $text = mb_strtolower(trim($mention->title . ' ' . $mention->snippet));
            $words = preg_split('/[^\p{L}\p{N}]+/u', $text) ?: [];

            foreach ($words as $word) {
                if (
                    mb_strlen($word) < $this->config->topicMinWordLength()
                    || in_array($word, $this->config->topicStopWords(), true)
                ) {
                    continue;
                }

                $bucket[$word] = (int) ($bucket[$word] ?? 0) + 1;
            }
        }

        arsort($bucket);

        $topics = [];
        foreach (array_slice($bucket, 0, $this->config->topicTopLimit(), true) as $word => $count) {
            $topics[] = new NewsTopicDTO(topic: (string) $word, count: (int) $count);
        }

        return $topics;
    }
}
