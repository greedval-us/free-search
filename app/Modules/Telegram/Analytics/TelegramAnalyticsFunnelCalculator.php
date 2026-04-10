<?php

namespace App\Modules\Telegram\Analytics;

class TelegramAnalyticsFunnelCalculator
{
    /**
     * @param array<int, array{views: int, interactions: int, reactions: int}> $candidates
     * @return array{
     *     stages: array<int, array{
     *         key: string,
     *         value: int,
     *         conversionFromPrevious: float,
     *         conversionFromStart: float
     *     }>,
     *     thresholds: array{views: int, interactions: int, reactions: int}
     * }
     */
    public function build(array $candidates): array
    {
        $viewThreshold = $this->resolvePositiveMedianThreshold(
            array_map(static fn (array $row): int => (int) $row['views'], $candidates)
        );
        $interactionThreshold = $this->resolvePositiveMedianThreshold(
            array_map(static fn (array $row): int => (int) $row['interactions'], $candidates)
        );
        $reactionThreshold = $this->resolvePositiveMedianThreshold(
            array_map(static fn (array $row): int => (int) $row['reactions'], $candidates)
        );

        $messages = count($candidates);

        $viewed = array_values(array_filter(
            $candidates,
            static fn (array $candidate): bool => ((int) ($candidate['views'] ?? 0)) >= $viewThreshold
        ));
        $interacted = array_values(array_filter(
            $viewed,
            static fn (array $candidate): bool => ((int) ($candidate['interactions'] ?? 0)) >= $interactionThreshold
        ));
        $reacted = array_values(array_filter(
            $interacted,
            static fn (array $candidate): bool => ((int) ($candidate['reactions'] ?? 0)) >= $reactionThreshold
        ));

        $stages = [
            ['key' => 'messages', 'value' => max(0, $messages)],
            ['key' => 'views', 'value' => count($viewed)],
            ['key' => 'interactions', 'value' => count($interacted)],
            ['key' => 'reactions', 'value' => count($reacted)],
        ];

        $start = (int) ($stages[0]['value'] ?? 0);
        $previous = $start;

        foreach ($stages as $index => $stage) {
            $value = (int) ($stage['value'] ?? 0);

            if ($index === 0) {
                $stages[$index]['conversionFromPrevious'] = 100.0;
                $stages[$index]['conversionFromStart'] = 100.0;
                $previous = $value;

                continue;
            }

            $stages[$index]['conversionFromPrevious'] = $previous > 0
                ? round(($value / $previous) * 100, 1)
                : 0.0;
            $stages[$index]['conversionFromStart'] = $start > 0
                ? round(($value / $start) * 100, 1)
                : 0.0;
            $previous = $value;
        }

        return [
            'stages' => $stages,
            'thresholds' => [
                'views' => $viewThreshold,
                'interactions' => $interactionThreshold,
                'reactions' => $reactionThreshold,
            ],
        ];
    }

    /**
     * @param array<int, int> $values
     */
    private function resolvePositiveMedianThreshold(array $values): int
    {
        $positive = array_values(array_filter($values, static fn (int $value): bool => $value > 0));
        if ($positive === []) {
            return 1;
        }

        sort($positive);
        $count = count($positive);
        $middle = intdiv($count, 2);

        if ($count % 2 === 1) {
            return max(1, (int) $positive[$middle]);
        }

        return max(1, (int) round(($positive[$middle - 1] + $positive[$middle]) / 2));
    }
}
