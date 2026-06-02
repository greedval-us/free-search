<?php

namespace App\Modules\Mastodon\Presenters;

final class MastodonAccountPresenter
{
    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    public function present(array $item): array
    {
        return [
            'id' => (string) ($item['id'] ?? ''),
            'username' => (string) ($item['username'] ?? ''),
            'acct' => (string) ($item['acct'] ?? ''),
            'displayName' => $this->plainText((string) ($item['display_name'] ?? '')),
            'url' => (string) ($item['url'] ?? ''),
            'avatar' => (string) ($item['avatar'] ?? ''),
            'header' => (string) ($item['header'] ?? ''),
            'discoverable' => (bool) ($item['discoverable'] ?? false),
            'locked' => (bool) ($item['locked'] ?? false),
            'bot' => (bool) ($item['bot'] ?? false),
            'note' => $this->plainText((string) ($item['note'] ?? '')),
            'followersCount' => (int) ($item['followers_count'] ?? 0),
            'followingCount' => (int) ($item['following_count'] ?? 0),
            'statusesCount' => (int) ($item['statuses_count'] ?? 0),
            'lastStatusAt' => (string) ($item['last_status_at'] ?? ''),
        ];
    }

    private function plainText(string $value): string
    {
        return trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
