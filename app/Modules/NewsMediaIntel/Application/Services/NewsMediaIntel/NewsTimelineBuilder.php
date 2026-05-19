<?php

namespace App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel;

use App\Modules\NewsMediaIntel\Domain\DTO\NewsMentionDTO;
use App\Modules\NewsMediaIntel\Domain\DTO\TimelinePointDTO;

final class NewsTimelineBuilder
{
    /**
     * @param array<int, NewsMentionDTO> $mentions
     * @return array<int, TimelinePointDTO>
     */
    public function build(array $mentions): array
    {
        $timeline = [];

        foreach ($mentions as $mention) {
            $raw = $mention->publishedAt;
            $timestamp = strtotime($raw);
            $date = $timestamp !== false ? date('Y-m-d', $timestamp) : null;

            if ($date === null || $date === '1970-01-01') {
                continue;
            }

            $timeline[$date] = (int) ($timeline[$date] ?? 0) + 1;
        }

        // Fallback: if sources returned mentions without parseable dates,
        // keep timeline visible instead of an empty chart.
        if ($timeline === [] && $mentions !== []) {
            $timeline[date('Y-m-d')] = count($mentions);
        }

        ksort($timeline);

        $points = [];
        foreach ($timeline as $date => $count) {
            $points[] = new TimelinePointDTO(date: $date, mentions: (int) $count);
        }

        return $points;
    }
}
