<?php

namespace App\Modules\Username;

use App\Modules\Username\DTO\UsernameSourceCheckResultDTO;

final class UsernameConfidenceSummaryBuilder
{
    /**
     * @param array<int, UsernameSourceCheckResultDTO> $items
     * @return array<string, int|float>
     */
    public function build(array $items): array
    {
        if ($items === []) {
            return [
                'average' => 0,
                'high' => 0,
                'medium' => 0,
                'low' => 0,
            ];
        }

        $total = 0;
        $high = 0;
        $medium = 0;
        $low = 0;

        foreach ($items as $item) {
            $total += $item->confidence;

            if ($item->confidence >= 75) {
                $high++;
                continue;
            }

            if ($item->confidence >= 45) {
                $medium++;
                continue;
            }

            $low++;
        }

        return [
            'average' => round($total / count($items), 1),
            'high' => $high,
            'medium' => $medium,
            'low' => $low,
        ];
    }
}
