<?php

namespace App\Modules\Mastodon\Presenters;

final class MastodonHashtagPresenter
{
    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    public function present(array $item): array
    {
        return [
            'id' => (string) ($item['id'] ?? ''),
            'name' => (string) ($item['name'] ?? ''),
            'url' => (string) ($item['url'] ?? ''),
            'history' => collect($item['history'] ?? [])
                ->map(fn (array $history): array => [
                    'day' => $this->formatHistoryDay((string) ($history['day'] ?? '')),
                    'uses' => (int) ($history['uses'] ?? 0),
                    'accounts' => (int) ($history['accounts'] ?? 0),
                ])
                ->values()
                ->all(),
        ];
    }

    private function formatHistoryDay(string $value): string
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return '';
        }

        if (ctype_digit($trimmed)) {
            $timestamp = (int) $trimmed;

            if ($timestamp > 0) {
                return gmdate('Y-m-d', $timestamp);
            }
        }

        $timestamp = strtotime($trimmed);

        if ($timestamp === false) {
            return $trimmed;
        }

        return gmdate('Y-m-d', $timestamp);
    }
}
