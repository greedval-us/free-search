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
        $url = (string) ($item['url'] ?? '');

        return [
            'id' => (string) ($item['id'] ?? ''),
            'username' => (string) ($item['username'] ?? ''),
            'acct' => (string) ($item['acct'] ?? ''),
            'displayName' => $this->plainText((string) ($item['display_name'] ?? '')),
            'url' => $url,
            'avatar' => (string) ($item['avatar'] ?? ''),
            'header' => (string) ($item['header'] ?? ''),
            'discoverable' => (bool) ($item['discoverable'] ?? false),
            'locked' => (bool) ($item['locked'] ?? false),
            'bot' => (bool) ($item['bot'] ?? false),
            'group' => (bool) ($item['group'] ?? false),
            'note' => $this->plainText((string) ($item['note'] ?? '')),
            'createdAt' => (string) ($item['created_at'] ?? ''),
            'followersCount' => (int) ($item['followers_count'] ?? 0),
            'followingCount' => (int) ($item['following_count'] ?? 0),
            'statusesCount' => (int) ($item['statuses_count'] ?? 0),
            'lastStatusAt' => (string) ($item['last_status_at'] ?? ''),
            'instanceDomain' => $this->resolveDomain($url),
            'fields' => collect($item['fields'] ?? [])
                ->map(fn (array $field): array => [
                    'name' => $this->plainText((string) ($field['name'] ?? '')),
                    'value' => $this->plainText((string) ($field['value'] ?? '')),
                    'verifiedAt' => (string) ($field['verified_at'] ?? ''),
                ])
                ->filter(fn (array $field): bool => $field['name'] !== '' || $field['value'] !== '')
                ->values()
                ->all(),
        ];
    }

    private function resolveDomain(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) ? strtolower($host) : '';
    }

    private function plainText(string $value): string
    {
        return trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
