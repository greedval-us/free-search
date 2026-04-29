<?php

namespace App\Modules\EmailIntel\Application\Services\EmailIntel;

final class EmailRiskBreakdownBuilder
{
    /**
     * @param array<int, array{type: string, level: string, message: string}> $signals
     * @return array{items: array<int, array{type: string, level: string, message: string, points: int}>, total: int}
     */
    public function build(array $signals): array
    {
        $items = [];
        $total = 0;

        foreach ($signals as $signal) {
            $points = $this->pointsForLevel($signal['level']);
            $total += $points;

            $items[] = [
                'type' => $signal['type'],
                'level' => $signal['level'],
                'message' => $signal['message'],
                'points' => $points,
            ];
        }

        return [
            'items' => $items,
            'total' => min(100, $total),
        ];
    }

    private function pointsForLevel(string $level): int
    {
        return match ($level) {
            'high' => 30,
            'medium' => 15,
            'low' => 7,
            default => 0,
        };
    }
}
