<?php

namespace App\Modules\DocumentIntel\Infrastructure\Clients;

use App\Modules\DocumentIntel\Application\Contracts\DocumentMetadataExtractorInterface;
use App\Modules\DocumentIntel\Application\Services\DocumentIntel\DocumentArtifactExtractor;
use Illuminate\Support\Facades\Http;

final class DocumentMetadataExtractor implements DocumentMetadataExtractorInterface
{
    public function __construct(
        private readonly DocumentArtifactExtractor $artifactExtractor,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function extract(string $url): array
    {
        $extension = $this->detectExtension($url);
        $fallback = [
            'url' => $url,
            'extension' => $extension,
            'sizeBytes' => null,
            'contentType' => null,
            'lastModified' => null,
            'author' => null,
            'software' => null,
            'createdAt' => null,
            'modifiedAt' => null,
            'artifacts' => [
                'emails' => [],
                'usernames' => [],
                'paths' => [],
            ],
            'error' => null,
        ];

        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent(),
                'Accept' => '*/*',
            ])
                ->timeout($this->timeoutSeconds())
                ->withoutVerifying()
                ->get($url);

            if (!$response->successful()) {
                $fallback['error'] = 'http_' . $response->status();

                return $fallback;
            }

            $body = $response->body();
            $size = strlen($body);
            if ($size > $this->maxFileSizeBytes()) {
                $fallback['sizeBytes'] = $size;
                $fallback['contentType'] = $response->header('Content-Type');
                $fallback['lastModified'] = $response->header('Last-Modified');
                $fallback['error'] = 'file_too_large';

                return $fallback;
            }

            $meta = $fallback;
            $meta['sizeBytes'] = $size;
            $meta['contentType'] = $response->header('Content-Type');
            $meta['lastModified'] = $response->header('Last-Modified');

            if ($extension === 'pdf') {
                $meta = $this->extractPdfMetadata($body, $meta);
            }

            if (in_array($extension, ['docx', 'xlsx', 'pptx'], true)) {
                $meta = $this->extractOpenXmlMetadata($body, $meta);
            }

            $meta['artifacts'] = $this->artifactExtractor->extract($this->extractTextForArtifacts($body, $extension));

            return $meta;
        } catch (\Throwable) {
            $fallback['error'] = 'network_error';

            return $fallback;
        }
    }

    /**
     * @param array<string, mixed> $meta
     * @return array<string, mixed>
     */
    private function extractPdfMetadata(string $body, array $meta): array
    {
        $patterns = [
            'author' => '/\/Author\s*\(([^\)]{1,200})\)/',
            'software' => '/\/(Creator|Producer)\s*\(([^\)]{1,200})\)/',
            'createdAt' => '/\/CreationDate\s*\(([^\)]{1,60})\)/',
            'modifiedAt' => '/\/ModDate\s*\(([^\)]{1,60})\)/',
        ];

        if (preg_match($patterns['author'], $body, $m) === 1) {
            $meta['author'] = trim($m[1]);
        }

        if (preg_match($patterns['software'], $body, $m) === 1) {
            $meta['software'] = trim($m[2] ?? $m[1]);
        }

        if (preg_match($patterns['createdAt'], $body, $m) === 1) {
            $meta['createdAt'] = trim($m[1]);
        }

        if (preg_match($patterns['modifiedAt'], $body, $m) === 1) {
            $meta['modifiedAt'] = trim($m[1]);
        }

        return $meta;
    }

    /**
     * @param array<string, mixed> $meta
     * @return array<string, mixed>
     */
    private function extractOpenXmlMetadata(string $body, array $meta): array
    {
        if (!class_exists(\ZipArchive::class)) {
            return $meta;
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'docintel_');
        if ($tmpFile === false) {
            return $meta;
        }

        file_put_contents($tmpFile, $body);

        $zip = new \ZipArchive();
        if ($zip->open($tmpFile) !== true) {
            @unlink($tmpFile);

            return $meta;
        }

        $coreXml = $zip->getFromName('docProps/core.xml');
        $appXml = $zip->getFromName('docProps/app.xml');
        $zip->close();
        @unlink($tmpFile);

        if (is_string($coreXml)) {
            if (preg_match('/<dc:creator>([^<]{1,200})<\/dc:creator>/i', $coreXml, $m) === 1) {
                $meta['author'] = trim($m[1]);
            }
            if (preg_match('/<dcterms:created[^>]*>([^<]{1,80})<\/dcterms:created>/i', $coreXml, $m) === 1) {
                $meta['createdAt'] = trim($m[1]);
            }
            if (preg_match('/<dcterms:modified[^>]*>([^<]{1,80})<\/dcterms:modified>/i', $coreXml, $m) === 1) {
                $meta['modifiedAt'] = trim($m[1]);
            }
        }

        if (is_string($appXml) && preg_match('/<Application>([^<]{1,200})<\/Application>/i', $appXml, $m) === 1) {
            $meta['software'] = trim($m[1]);
        }

        return $meta;
    }

    private function detectExtension(string $url): string
    {
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));
        $dotPos = strrpos($path, '.');

        return $dotPos === false ? '' : trim(substr($path, $dotPos + 1));
    }

    private function maxFileSizeBytes(): int
    {
        return (int) config('osint.document_intel.discovery.max_file_size_bytes', 5_000_000);
    }

    private function timeoutSeconds(): int
    {
        return (int) config('osint.document_intel.http.timeout_seconds', 10);
    }

    private function userAgent(): string
    {
        return (string) config('osint.document_intel.http.user_agent', 'FreeSearch-DocumentIntel/1.0');
    }

    private function extractTextForArtifacts(string $body, string $extension): string
    {
        if ($extension === 'pdf') {
            return preg_replace('/[^\\PC\\s]/u', ' ', $body) ?? '';
        }

        if (!in_array($extension, ['docx', 'xlsx', 'pptx'], true)) {
            return '';
        }

        if (!class_exists(\ZipArchive::class)) {
            return '';
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'docintel_txt_');
        if ($tmpFile === false) {
            return '';
        }

        file_put_contents($tmpFile, $body);
        $zip = new \ZipArchive();
        if ($zip->open($tmpFile) !== true) {
            @unlink($tmpFile);

            return '';
        }

        $parts = [];
        $targets = [
            'word/document.xml',
            'xl/sharedStrings.xml',
            'ppt/slides/slide1.xml',
            'docProps/core.xml',
            'docProps/app.xml',
        ];

        foreach ($targets as $entry) {
            $content = $zip->getFromName($entry);
            if (is_string($content)) {
                $parts[] = strip_tags($content);
            }
        }

        $zip->close();
        @unlink($tmpFile);

        return trim(implode(' ', $parts));
    }
}

