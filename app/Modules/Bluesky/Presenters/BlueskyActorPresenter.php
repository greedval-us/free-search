<?php

namespace App\Modules\Bluesky\Presenters;

final class BlueskyActorPresenter
{
    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    public function present(array $item): array
    {
        return [
            'did' => (string) ($item['did'] ?? ''),
            'handle' => (string) ($item['handle'] ?? ''),
            'displayName' => trim((string) ($item['displayName'] ?? '')),
            'description' => trim((string) ($item['description'] ?? '')),
            'avatar' => (string) ($item['avatar'] ?? ''),
            'banner' => (string) ($item['banner'] ?? ''),
            'url' => $this->profileUrl((string) ($item['handle'] ?? '')),
            'followersCount' => (int) ($item['followersCount'] ?? 0),
            'followsCount' => (int) ($item['followsCount'] ?? 0),
            'postsCount' => (int) ($item['postsCount'] ?? 0),
            'indexedAt' => (string) ($item['indexedAt'] ?? ''),
            'createdAt' => (string) ($item['createdAt'] ?? ''),
            'labels' => collect($item['labels'] ?? [])
                ->map(fn (array $label): string => (string) ($label['val'] ?? ''))
                ->filter()
                ->values()
                ->all(),
        ];
    }

    private function profileUrl(string $handle): string
    {
        $normalized = trim($handle);

        return $normalized !== '' ? sprintf('https://bsky.app/profile/%s', $normalized) : '';
    }
}
