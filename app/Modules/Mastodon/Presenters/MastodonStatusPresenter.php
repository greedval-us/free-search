<?php

namespace App\Modules\Mastodon\Presenters;

use Illuminate\Support\Arr;

final class MastodonStatusPresenter
{
    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    public function present(array $item): array
    {
        return [
            'id' => (string) ($item['id'] ?? ''),
            'url' => (string) ($item['url'] ?? ''),
            'uri' => (string) ($item['uri'] ?? ''),
            'createdAt' => (string) ($item['created_at'] ?? ''),
            'content' => $this->plainText((string) ($item['content'] ?? '')),
            'language' => (string) ($item['language'] ?? ''),
            'visibility' => (string) ($item['visibility'] ?? ''),
            'sensitive' => (bool) ($item['sensitive'] ?? false),
            'repliesCount' => (int) ($item['replies_count'] ?? 0),
            'reblogsCount' => (int) ($item['reblogs_count'] ?? 0),
            'favouritesCount' => (int) ($item['favourites_count'] ?? 0),
            'mediaAttachmentsCount' => count(Arr::get($item, 'media_attachments', [])),
            'tags' => collect(Arr::get($item, 'tags', []))
                ->map(fn (array $tag): string => (string) ($tag['name'] ?? ''))
                ->filter()
                ->values()
                ->all(),
            'account' => [
                'id' => (string) Arr::get($item, 'account.id', ''),
                'username' => (string) Arr::get($item, 'account.username', ''),
                'acct' => (string) Arr::get($item, 'account.acct', ''),
                'displayName' => $this->plainText((string) Arr::get($item, 'account.display_name', '')),
                'url' => (string) Arr::get($item, 'account.url', ''),
                'avatar' => (string) Arr::get($item, 'account.avatar', ''),
            ],
        ];
    }

    private function plainText(string $value): string
    {
        return trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
