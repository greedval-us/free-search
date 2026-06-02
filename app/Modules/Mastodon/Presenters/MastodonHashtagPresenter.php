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
            'name' => (string) ($item['name'] ?? ''),
            'url' => (string) ($item['url'] ?? ''),
            'history' => collect($item['history'] ?? [])
                ->map(fn (array $history): array => [
                    'day' => (string) ($history['day'] ?? ''),
                    'uses' => (int) ($history['uses'] ?? 0),
                    'accounts' => (int) ($history['accounts'] ?? 0),
                ])
                ->values()
                ->all(),
        ];
    }
}
