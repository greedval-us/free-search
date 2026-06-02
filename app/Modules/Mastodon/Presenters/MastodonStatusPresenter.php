<?php

namespace App\Modules\Mastodon\Presenters;

use App\Modules\Mastodon\Enums\MastodonPostType;
use Illuminate\Support\Arr;

final class MastodonStatusPresenter
{
    public function __construct(
        private readonly MastodonAccountPresenter $accountPresenter,
    ) {
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    public function present(array $item): array
    {
        $content = $this->plainText((string) ($item['content'] ?? ''));
        $accountUrl = (string) Arr::get($item, 'account.url', '');
        $statusUrl = (string) ($item['url'] ?? '');
        $links = $this->extractLinks($item);
        $domains = $this->extractDomains($links);
        $mediaAttachments = Arr::get($item, 'media_attachments', []);

        return [
            'id' => (string) ($item['id'] ?? ''),
            'url' => $statusUrl,
            'uri' => (string) ($item['uri'] ?? ''),
            'inReplyToId' => empty($item['in_reply_to_id']) ? null : (string) $item['in_reply_to_id'],
            'createdAt' => (string) ($item['created_at'] ?? ''),
            'content' => $content,
            'spoilerText' => $this->plainText((string) ($item['spoiler_text'] ?? '')),
            'language' => (string) ($item['language'] ?? ''),
            'visibility' => (string) ($item['visibility'] ?? ''),
            'sensitive' => (bool) ($item['sensitive'] ?? false),
            'repliesCount' => (int) ($item['replies_count'] ?? 0),
            'reblogsCount' => (int) ($item['reblogs_count'] ?? 0),
            'favouritesCount' => (int) ($item['favourites_count'] ?? 0),
            'mediaAttachmentsCount' => count($mediaAttachments),
            'hasMedia' => count($mediaAttachments) > 0,
            'hasLinks' => $links !== [],
            'postType' => $this->resolvePostType($item),
            'instanceDomain' => $this->resolveDomain($accountUrl !== '' ? $accountUrl : $statusUrl),
            'links' => $links,
            'domains' => $domains,
            'mentions' => $this->presentMentions($item),
            'tags' => $this->presentTags($item),
            'account' => $this->accountPresenter->present((array) ($item['account'] ?? [])),
        ];
    }

    /**
     * @param array<string, mixed> $item
     * @return array<int, array<string, string>>
     */
    private function presentMentions(array $item): array
    {
        return collect(Arr::get($item, 'mentions', []))
            ->map(fn (array $mention): array => [
                'id' => (string) ($mention['id'] ?? ''),
                'username' => (string) ($mention['username'] ?? ''),
                'acct' => (string) ($mention['acct'] ?? ''),
                'url' => (string) ($mention['url'] ?? ''),
            ])
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $item
     * @return array<int, string>
     */
    private function presentTags(array $item): array
    {
        return collect(Arr::get($item, 'tags', []))
            ->map(fn (array $tag): string => (string) ($tag['name'] ?? ''))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $item
     * @return array<int, string>
     */
    private function extractLinks(array $item): array
    {
        $html = (string) ($item['content'] ?? '');

        if ($html === '') {
            return [];
        }

        preg_match_all('/href="([^"]+)"/i', $html, $matches);

        return collect($matches[1] ?? [])
            ->map(fn (mixed $value): string => trim((string) $value))
            ->filter(fn (string $value): bool => str_starts_with($value, 'http://') || str_starts_with($value, 'https://'))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<int, string> $links
     * @return array<int, string>
     */
    private function extractDomains(array $links): array
    {
        return collect($links)
            ->map(fn (string $link): string => $this->resolveDomain($link))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $item
     */
    private function resolvePostType(array $item): string
    {
        if (Arr::get($item, 'reblog.id')) {
            return MastodonPostType::Boost->value;
        }

        if (! empty($item['in_reply_to_id'])) {
            return MastodonPostType::Reply->value;
        }

        return MastodonPostType::Original->value;
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
