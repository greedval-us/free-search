<?php

namespace App\Modules\DocumentIntel\Application\Services\DocumentIntel;

final class DocumentPivotBuilder
{
    /**
     * @return array<int, array{label: string, url: string}>
     */
    public function build(string $query, ?string $domain): array
    {
        $target = $domain ?? $query;
        $encodedTarget = rawurlencode($target);

        return [
            ['label' => 'pdf_search', 'url' => 'https://www.google.com/search?q=' . rawurlencode('site:' . $target . ' filetype:pdf')],
            ['label' => 'docx_search', 'url' => 'https://www.google.com/search?q=' . rawurlencode('site:' . $target . ' filetype:docx')],
            ['label' => 'xlsx_search', 'url' => 'https://www.google.com/search?q=' . rawurlencode('site:' . $target . ' filetype:xlsx')],
            ['label' => 'pptx_search', 'url' => 'https://www.google.com/search?q=' . rawurlencode('site:' . $target . ' filetype:pptx')],
            ['label' => 'robots_txt', 'url' => 'https://' . $encodedTarget . '/robots.txt'],
            ['label' => 'sitemap_xml', 'url' => 'https://' . $encodedTarget . '/sitemap.xml'],
            ['label' => 'wayback_docs', 'url' => 'https://web.archive.org/web/*/' . $encodedTarget . '/*'],
            ['label' => 'github_leaks', 'url' => 'https://github.com/search?q=' . rawurlencode($target . ' password OR confidential OR internal')],
        ];
    }
}
