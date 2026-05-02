<?php

namespace App\Modules\SiteIntel\Application\Services\SeoAudit;

final class SeoAuditQualityAnalyzer
{
    /**
     * @return array<string, mixed>
     */
    public function analyze(string $html, string $baseUrl): array
    {
        return [
            'anchors' => $this->analyzeAnchors($html),
            'htmlValidation' => $this->analyzeHtmlValidation($html),
            'accessibility' => $this->analyzeAccessibility($html),
            'content' => $this->analyzeContent($html),
            'linkGraph' => $this->analyzeLinkGraph($html, $baseUrl),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeAnchors(string $html): array
    {
        preg_match_all('/<a\b[^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER);

        $total = 0;
        $empty = 0;
        $generic = 0;
        $genericTokens = ['click here', 'read more', 'more', 'here', 'далее', 'подробнее', 'читать'];

        foreach ($matches as $match) {
            $total++;
            $text = trim(mb_strtolower(strip_tags((string) ($match[1] ?? ''))));

            if ($text === '') {
                $empty++;
                continue;
            }

            if (in_array($text, $genericTokens, true)) {
                $generic++;
            }
        }

        return [
            'total' => $total,
            'empty' => $empty,
            'generic' => $generic,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeHtmlValidation(string $html): array
    {
        $doctype = preg_match('/<!doctype\s+html>/i', $html) === 1;
        $htmlTag = preg_match('/<html\b[^>]*>/i', $html) === 1;
        $headTag = preg_match('/<head\b[^>]*>/i', $html) === 1;
        $bodyTag = preg_match('/<body\b[^>]*>/i', $html) === 1;
        $multipleH1 = (preg_match_all('/<h1\b[^>]*>/i', $html) ?: 0) > 1;

        $issues = [];
        if (!$doctype) {
            $issues[] = 'missing_doctype';
        }
        if (!$htmlTag) {
            $issues[] = 'missing_html_tag';
        }
        if (!$headTag) {
            $issues[] = 'missing_head_tag';
        }
        if (!$bodyTag) {
            $issues[] = 'missing_body_tag';
        }
        if ($multipleH1) {
            $issues[] = 'multiple_h1';
        }

        return [
            'issues' => $issues,
            'issueCount' => count($issues),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeAccessibility(string $html): array
    {
        preg_match_all('/<img\b[^>]*>/i', $html, $images);
        $imagesTotal = count($images[0] ?? []);
        $imagesWithoutAlt = 0;

        foreach (($images[0] ?? []) as $imgTag) {
            if (preg_match('/\balt\s*=\s*(["\']).*?\1/i', (string) $imgTag) !== 1) {
                $imagesWithoutAlt++;
            }
        }

        preg_match_all('/<input\b[^>]*>/i', $html, $inputs);
        $inputsTotal = count($inputs[0] ?? []);

        preg_match_all('/<label\b[^>]*for\s*=\s*(["\']).*?\1[^>]*>/i', $html, $labels);
        $labelsTotal = count($labels[0] ?? []);

        $headingOrderBroken = preg_match('/<h1\b[^>]*>.*?<h3\b/is', $html) === 1 && preg_match('/<h2\b/i', $html) !== 1;

        return [
            'imagesTotal' => $imagesTotal,
            'imagesWithoutAlt' => $imagesWithoutAlt,
            'inputsTotal' => $inputsTotal,
            'labelsTotal' => $labelsTotal,
            'headingOrderBroken' => $headingOrderBroken,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeContent(string $html): array
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? '');
        $textLength = mb_strlen($text);
        $wordCount = $text === '' ? 0 : count(preg_split('/\s+/u', $text) ?: []);
        $htmlLength = max(1, strlen($html));
        $textToHtmlRatio = (float) round(($textLength / $htmlLength) * 100, 2);

        $thinContent = $wordCount > 0 && $wordCount < 250;
        $lowTextRatio = $textToHtmlRatio < 12.0;

        return [
            'wordCount' => $wordCount,
            'textLength' => $textLength,
            'textToHtmlRatio' => $textToHtmlRatio,
            'thinContent' => $thinContent,
            'lowTextRatio' => $lowTextRatio,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeLinkGraph(string $html, string $baseUrl): array
    {
        $host = (string) parse_url($baseUrl, PHP_URL_HOST);
        preg_match_all('/<a\b[^>]*href\s*=\s*(["\'])(.*?)\1[^>]*>/is', $html, $links, PREG_SET_ORDER);

        $internal = 0;
        $external = 0;
        foreach ($links as $link) {
            $href = trim((string) ($link[2] ?? ''));
            if ($href === '' || str_starts_with($href, '#')) {
                continue;
            }

            if (str_starts_with($href, '/')) {
                $internal++;
                continue;
            }

            $hrefHost = (string) parse_url($href, PHP_URL_HOST);
            if ($hrefHost === '' || $host === '' || mb_strtolower($hrefHost) === mb_strtolower($host)) {
                $internal++;
            } else {
                $external++;
            }
        }

        return [
            'internalOutlinks' => $internal,
            'externalOutlinks' => $external,
            'orphanRisk' => $internal === 0,
        ];
    }
}

