<?php

namespace App\Modules\Telegram\Analytics;

use Carbon\Carbon;

class TelegramAnalyticsAudienceCalculator
{
    /**
     * @param array<string, array<string, mixed>> $authorStats
     * @param array<int, int> $hourlyActivity
     * @return array{
     *     activeAuthors: int,
     *     singleMessageAuthors: int,
     *     returningAuthors: int,
     *     topAuthorShare: float,
     *     top5AuthorsShare: float,
     *     concentrationIndex: float,
     *     mostActiveHours: array<int, array{hour: int, label: string, messages: int}>
     * }
     */
    public function build(array $authorStats, int $totalMessages, array $hourlyActivity): array
    {
        $activeAuthors = count($authorStats);
        $singleMessageAuthors = 0;
        $returningAuthors = 0;
        $messagesByAuthors = [];

        foreach ($authorStats as $stat) {
            $messages = max(0, (int) ($stat['messages'] ?? 0));
            $messagesByAuthors[] = $messages;

            if ($messages <= 1) {
                $singleMessageAuthors++;
            } else {
                $returningAuthors++;
            }
        }

        rsort($messagesByAuthors);

        $topAuthorMessages = $messagesByAuthors[0] ?? 0;
        $top5AuthorsMessages = array_sum(array_slice($messagesByAuthors, 0, 5));
        $safeTotalMessages = max(1, $totalMessages);
        $concentration = 0.0;

        foreach ($messagesByAuthors as $messages) {
            $share = $messages / $safeTotalMessages;
            $concentration += $share * $share;
        }

        $mostActiveHours = [];
        foreach ($hourlyActivity as $hour => $messages) {
            $mostActiveHours[] = [
                'hour' => $hour,
                'label' => sprintf('%02d:00', $hour),
                'messages' => $messages,
            ];
        }

        usort(
            $mostActiveHours,
            static fn (array $left, array $right): int => $right['messages'] <=> $left['messages']
        );

        return [
            'activeAuthors' => $activeAuthors,
            'singleMessageAuthors' => $singleMessageAuthors,
            'returningAuthors' => $returningAuthors,
            'topAuthorShare' => round(($topAuthorMessages / $safeTotalMessages) * 100, 1),
            'top5AuthorsShare' => round(($top5AuthorsMessages / $safeTotalMessages) * 100, 1),
            'concentrationIndex' => round($concentration, 4),
            'mostActiveHours' => array_slice($mostActiveHours, 0, 3),
        ];
    }

    /**
     * @param array<int, int> $hourlyActivity
     */
    public function accumulateHourActivity(array &$hourlyActivity, int $timestamp): void
    {
        if ($timestamp <= 0) {
            return;
        }

        $hour = (int) Carbon::createFromTimestamp($timestamp, config('app.timezone'))->format('G');
        if ($hour < 0 || $hour > 23) {
            return;
        }

        $hourlyActivity[$hour]++;
    }
}
