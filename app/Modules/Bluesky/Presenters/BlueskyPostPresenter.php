<?php

namespace App\Modules\Bluesky\Presenters;

use Illuminate\Support\Arr;

final class BlueskyPostPresenter
{
    public function __construct(
        private readonly BlueskyActorPresenter $actorPresenter,
    ) {
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    public function present(array $item): array
    {
        $record = (array) ($item['record'] ?? []);
        $author = (array) ($item['author'] ?? []);
        $links = $this->extractLinks($record, $item);

        return [
            'id' => (string) ($item['uri'] ?? ''),
            'uri' => (string) ($item['uri'] ?? ''),
            'cid' => (string) ($item['cid'] ?? ''),
            'url' => $this->resolvePostUrl((string) ($item['uri'] ?? ''), (string) ($author['handle'] ?? '')),
            'text' => trim((string) ($record['text'] ?? '')),
            'createdAt' => (string) ($record['createdAt'] ?? $item['indexedAt'] ?? ''),
            'indexedAt' => (string) ($item['indexedAt'] ?? ''),
            'labels' => collect($item['labels'] ?? [])
                ->map(fn (array $label): string => (string) ($label['val'] ?? ''))
                ->filter()
                ->values()
                ->all(),
            'replyCount' => (int) ($item['replyCount'] ?? 0),
            'repostCount' => (int) ($item['repostCount'] ?? 0),
            'likeCount' => (int) ($item['likeCount'] ?? 0),
            'quoteCount' => (int) ($item['quoteCount'] ?? 0),
            'replyRootUri' => (string) Arr::get($record, 'reply.root.uri', ''),
            'replyParentUri' => (string) Arr::get($record, 'reply.parent.uri', ''),
            'languages' => collect($record['langs'] ?? [])
                ->map(fn (mixed $value): string => strtolower((string) $value))
                ->filter()
                ->values()
                ->all(),
            'hashtags' => $this->extractHashtags($record),
            'mentions' => $this->extractMentions($record),
            'links' => $links,
            'domains' => collect($links)
                ->map(fn (string $value): string => $this->resolveDomain($value))
                ->filter()
                ->unique()
                ->values()
                ->all(),
            'media' => $this->extractMedia($item),
            'hasMedia' => $this->hasMediaEmbed($item),
            'hasLinks' => $links !== [],
            'postType' => Arr::has($record, 'reply') ? 'reply' : 'post',
            'author' => $this->actorPresenter->present($author),
        ];
    }

    /**
     * @param array<string, mixed> $record
     * @param array<string, mixed> $item
     * @return array<int, string>
     */
    private function extractLinks(array $record, array $item): array
    {
        $facetLinks = collect($record['facets'] ?? [])
            ->flatMap(function (array $facet): array {
                return collect($facet['features'] ?? [])
                    ->map(fn (array $feature): string => trim((string) ($feature['uri'] ?? '')))
                    ->filter()
                    ->values()
                    ->all();
            });

        $embedExternalLink = trim((string) Arr::get($item, 'embed.external.uri', ''));

        return $facetLinks
            ->push($embedExternalLink)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $record
     * @return array<int, string>
     */
    private function extractHashtags(array $record): array
    {
        return collect($record['facets'] ?? [])
            ->flatMap(function (array $facet): array {
                return collect($facet['features'] ?? [])
                    ->map(function (array $feature): string {
                        $tag = (string) ($feature['tag'] ?? '');

                        return ltrim($tag, '#');
                    })
                    ->filter()
                    ->values()
                    ->all();
            })
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $record
     * @return array<int, string>
     */
    private function extractMentions(array $record): array
    {
        return collect($record['facets'] ?? [])
            ->flatMap(function (array $facet): array {
                return collect($facet['features'] ?? [])
                    ->map(fn (array $feature): string => (string) ($feature['did'] ?? ''))
                    ->filter()
                    ->values()
                    ->all();
            })
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $item
     */
    private function hasMediaEmbed(array $item): bool
    {
        $embedType = $this->embedType($item);

        if ($embedType === '') {
            return false;
        }

        return str_contains($embedType, '.images')
            || str_contains($embedType, '.video')
            || str_contains($embedType, '.recordWithMedia');
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function extractMedia(array $item): array
    {
        $embedType = $this->embedType($item);
        $images = collect(Arr::get($item, 'embed.images', []))
            ->map(fn (array $image): array => [
                'thumb' => (string) ($image['thumb'] ?? ''),
                'fullsize' => (string) ($image['fullsize'] ?? ''),
                'alt' => trim((string) ($image['alt'] ?? '')),
            ])
            ->values()
            ->all();

        $external = (array) Arr::get($item, 'embed.external', []);
        $video = [
            'playlist' => (string) Arr::get($item, 'embed.playlist', ''),
            'thumbnail' => (string) Arr::get($item, 'embed.thumbnail', ''),
            'alt' => trim((string) Arr::get($item, 'embed.alt', '')),
        ];

        if (str_contains($embedType, '.recordWithMedia')) {
            $images = collect(Arr::get($item, 'embed.media.images', []))
                ->map(fn (array $image): array => [
                    'thumb' => (string) ($image['thumb'] ?? ''),
                    'fullsize' => (string) ($image['fullsize'] ?? ''),
                    'alt' => trim((string) ($image['alt'] ?? '')),
                ])
                ->values()
                ->all();

            $external = (array) Arr::get($item, 'embed.media.external', []);
            $video = [
                'playlist' => (string) Arr::get($item, 'embed.media.playlist', ''),
                'thumbnail' => (string) Arr::get($item, 'embed.media.thumbnail', ''),
                'alt' => trim((string) Arr::get($item, 'embed.media.alt', '')),
            ];
        }

        return [
            'type' => $this->resolveMediaType($embedType),
            'images' => $images,
            'video' => $video,
            'external' => [
                'uri' => (string) ($external['uri'] ?? ''),
                'title' => trim((string) ($external['title'] ?? '')),
                'description' => trim((string) ($external['description'] ?? '')),
                'thumb' => (string) ($external['thumb'] ?? ''),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $item
     */
    private function embedType(array $item): string
    {
        return (string) (
            $item['embed']['$type']
            ?? Arr::get($item, 'embed.media.$type', '')
        );
    }

    private function resolveMediaType(string $embedType): string
    {
        if ($embedType === '') {
            return 'none';
        }

        if (str_contains($embedType, '.images')) {
            return 'images';
        }

        if (str_contains($embedType, '.video')) {
            return 'video';
        }

        if (str_contains($embedType, '.external')) {
            return 'external';
        }

        if (str_contains($embedType, '.recordWithMedia')) {
            return 'recordWithMedia';
        }

        if (str_contains($embedType, '.record')) {
            return 'record';
        }

        return 'other';
    }

    private function resolvePostUrl(string $uri, string $handle): string
    {
        if ($uri === '' || $handle === '') {
            return '';
        }

        $segments = explode('/', trim($uri));
        $rkey = end($segments);

        return $rkey !== false && $rkey !== ''
            ? sprintf('https://bsky.app/profile/%s/post/%s', $handle, $rkey)
            : '';
    }

    private function resolveDomain(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) ? strtolower($host) : '';
    }
}
