<?php

namespace App\Modules\DocumentIntel\Infrastructure\Clients;

use App\Modules\DocumentIntel\Application\Contracts\DocumentUrlCollectorInterface;
use App\Modules\DocumentIntel\Application\Support\DocumentIntelConfig;
use Illuminate\Support\Facades\Http;

final class DocumentUrlCollector implements DocumentUrlCollectorInterface
{
    public function __construct(private readonly DocumentIntelConfig $config)
    {
    }

    /**
     * @return array<int, string>
     */
    public function collect(string $domain): array
    {
        $urls = [];

        $sitemapUrl = 'https://' . $domain . '/sitemap.xml';
        $sitemapBody = $this->fetchText($sitemapUrl);

        if ($sitemapBody !== null) {
            $urls = array_merge($urls, $this->extractDocumentUrlsFromSitemap($sitemapBody));
        }

        $homepageUrl = 'https://' . $domain . '/';
        $homepageBody = $this->fetchText($homepageUrl);

        if ($homepageBody !== null) {
            $urls = array_merge($urls, $this->extractDocumentUrlsFromHtml($homepageBody, $domain));
        }

        $unique = array_values(array_unique($urls));

        return array_slice($unique, 0, $this->maxDocuments());
    }

    private function fetchText(string $url): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent(),
                'Accept' => 'text/html,application/xml,text/xml,*/*',
            ])
                ->timeout($this->timeoutSeconds())
                ->withoutVerifying()
                ->get($url);

            if (!$response->successful()) {
                return null;
            }

            return $response->body();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<int, string>
     */
    private function extractDocumentUrlsFromSitemap(string $body): array
    {
        preg_match_all('/<loc>([^<]+)<\/loc>/i', $body, $matches);

        $urls = [];
        foreach ($matches[1] ?? [] as $candidate) {
            if ($this->isDocumentUrl($candidate)) {
                $urls[] = trim($candidate);
            }
        }

        return $urls;
    }

    /**
     * @return array<int, string>
     */
    private function extractDocumentUrlsFromHtml(string $body, string $domain): array
    {
        preg_match_all('/href=["\']([^"\']+)["\']/i', $body, $matches);

        $urls = [];
        foreach ($matches[1] ?? [] as $href) {
            $absolute = $this->toAbsoluteUrl($href, $domain);

            if ($absolute !== null && $this->isDocumentUrl($absolute)) {
                $urls[] = $absolute;
            }
        }

        return $urls;
    }

    private function toAbsoluteUrl(string $href, string $domain): ?string
    {
        $trimmed = trim($href);

        if ($trimmed === '' || str_starts_with($trimmed, '#') || str_starts_with($trimmed, 'mailto:')) {
            return null;
        }

        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
            return $trimmed;
        }

        if (str_starts_with($trimmed, '/')) {
            return 'https://' . $domain . $trimmed;
        }

        return 'https://' . $domain . '/' . ltrim($trimmed, '/');
    }

    private function isDocumentUrl(string $url): bool
    {
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));

        foreach ($this->allowedExtensions() as $extension) {
            if (str_ends_with($path, '.' . $extension)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function allowedExtensions(): array
    {
        return $this->config->discoveryExtensions();
    }

    private function maxDocuments(): int
    {
        return $this->config->discoveryMaxDocuments();
    }

    private function timeoutSeconds(): int
    {
        return $this->config->httpTimeoutSeconds();
    }

    private function userAgent(): string
    {
        return $this->config->httpUserAgent();
    }
}
