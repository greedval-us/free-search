<?php

namespace App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel;

use App\Modules\NewsMediaIntel\Domain\DTO\NewsMentionDTO;
use App\Modules\NewsMediaIntel\Domain\DTO\SentimentSummaryDTO;

final class NewsSentimentAnalyzer
{
    /**
     * @param array<int, NewsMentionDTO> $mentions
     */
    public function summarize(array $mentions): SentimentSummaryDTO
    {
        $positiveWords = ['success', 'growth', 'win', 'award', 'profit', 'улучш', 'рост', 'успех'];
        $negativeWords = ['fraud', 'crime', 'attack', 'breach', 'loss', 'скандал', 'утеч', 'мошенн'];

        $positive = 0;
        $neutral = 0;
        $negative = 0;

        foreach ($mentions as $mention) {
            $text = mb_strtolower(trim($mention->title . ' ' . $mention->snippet));
            $score = 0;

            foreach ($positiveWords as $word) {
                if (str_contains($text, $word)) {
                    $score++;
                }
            }

            foreach ($negativeWords as $word) {
                if (str_contains($text, $word)) {
                    $score--;
                }
            }

            if ($score > 0) {
                $positive++;
            } elseif ($score < 0) {
                $negative++;
            } else {
                $neutral++;
            }
        }

        return new SentimentSummaryDTO(
            positive: $positive,
            neutral: $neutral,
            negative: $negative,
        );
    }
}

