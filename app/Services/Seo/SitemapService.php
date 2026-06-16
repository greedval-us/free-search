<?php

namespace App\Services\Seo;

final class SitemapService
{
    /**
     * @return list<array{loc: string, priority: string, changefreq: string}>
     */
    public function pages(): array
    {
        /** @var list<array{route: string, priority: string, changefreq: string}> $pages */
        $pages = config('seo.sitemap.pages', []);

        return array_map(
            static fn (array $page): array => [
                'loc' => route($page['route'], absolute: true),
                'priority' => $page['priority'],
                'changefreq' => $page['changefreq'],
            ],
            $pages
        );
    }

    public function toXml(): string
    {
        $lastmod = now()->toDateString();

        $items = collect($this->pages())
            ->map(function (array $page) use ($lastmod): string {
                $loc = htmlspecialchars($page['loc'], ENT_XML1);
                $priority = htmlspecialchars($page['priority'], ENT_XML1);
                $changefreq = htmlspecialchars($page['changefreq'], ENT_XML1);

                return <<<XML
    <url>
        <loc>{$loc}</loc>
        <lastmod>{$lastmod}</lastmod>
        <changefreq>{$changefreq}</changefreq>
        <priority>{$priority}</priority>
    </url>
XML;
            })
            ->implode("\n");

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
{$items}
</urlset>
XML;
    }
}
