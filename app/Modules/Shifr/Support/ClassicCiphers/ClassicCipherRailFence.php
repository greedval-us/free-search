<?php

namespace App\Modules\Shifr\Support\ClassicCiphers;

final class ClassicCipherRailFence
{
    public function encrypt(string $text, int $rails): string
    {
        $rails = max(2, $rails);
        $chars = mb_str_split($text);

        if (count($chars) <= 1) {
            return $text;
        }

        $rows = array_fill(0, $rails, '');
        $row = 0;
        $direction = 1;

        foreach ($chars as $char) {
            $rows[$row] .= $char;
            $row += $direction;
            if ($row === 0 || $row === $rails - 1) {
                $direction *= -1;
            }
        }

        return implode('', $rows);
    }

    public function decrypt(string $text, int $rails): string
    {
        $rails = max(2, $rails);
        $chars = mb_str_split($text);
        $length = count($chars);

        if ($length <= 1) {
            return $text;
        }

        $pattern = [];
        $row = 0;
        $direction = 1;
        for ($i = 0; $i < $length; $i++) {
            $pattern[] = $row;
            $row += $direction;
            if ($row === 0 || $row === $rails - 1) {
                $direction *= -1;
            }
        }

        $counts = array_fill(0, $rails, 0);
        foreach ($pattern as $r) {
            $counts[$r]++;
        }

        $rows = [];
        $offset = 0;
        for ($r = 0; $r < $rails; $r++) {
            $rows[$r] = array_slice($chars, $offset, $counts[$r]);
            $offset += $counts[$r];
        }

        $indices = array_fill(0, $rails, 0);
        $output = '';
        foreach ($pattern as $r) {
            $output .= $rows[$r][$indices[$r]] ?? '';
            $indices[$r]++;
        }

        return $output;
    }
}
