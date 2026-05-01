<?php

namespace App\Modules\SiteIntel\Application\Services\SeoAudit;

final class SeoAuditRobotsRulesParser
{
    /**
     * @return array<string, mixed>
     */
    public function parse(string $robotsTxt): array
    {
        if (trim($robotsTxt) === '') {
            return [
                'userAgents' => [],
                'groups' => [],
                'hasWildcardGroup' => false,
                'hasCrawlDelay' => false,
            ];
        }

        $lines = preg_split('/\r\n|\r|\n/', $robotsTxt) ?: [];
        $groups = [];
        $current = [
            'userAgents' => [],
            'allow' => [],
            'disallow' => [],
            'crawlDelay' => null,
        ];

        foreach ($lines as $line) {
            $clean = trim((string) preg_replace('/#.*/', '', (string) $line));
            if ($clean === '' || !str_contains($clean, ':')) {
                continue;
            }

            [$rawKey, $rawValue] = array_map('trim', explode(':', $clean, 2));
            $key = mb_strtolower($rawKey);
            $value = $rawValue;

            if ($key === 'user-agent') {
                if ($current['userAgents'] !== [] || $current['allow'] !== [] || $current['disallow'] !== [] || $current['crawlDelay'] !== null) {
                    $groups[] = $current;
                    $current = ['userAgents' => [], 'allow' => [], 'disallow' => [], 'crawlDelay' => null];
                }
                $current['userAgents'][] = $value;
                continue;
            }

            if ($key === 'allow') {
                $current['allow'][] = $value;
                continue;
            }

            if ($key === 'disallow') {
                $current['disallow'][] = $value;
                continue;
            }

            if ($key === 'crawl-delay') {
                $current['crawlDelay'] = is_numeric($value) ? (float) $value : null;
            }
        }

        if ($current['userAgents'] !== [] || $current['allow'] !== [] || $current['disallow'] !== [] || $current['crawlDelay'] !== null) {
            $groups[] = $current;
        }

        $userAgents = [];
        $hasWildcard = false;
        $hasCrawlDelay = false;

        foreach ($groups as $group) {
            foreach ($group['userAgents'] as $ua) {
                $uaLower = mb_strtolower((string) $ua);
                $userAgents[] = $uaLower;
                if ($uaLower === '*') {
                    $hasWildcard = true;
                }
            }
            if ($group['crawlDelay'] !== null) {
                $hasCrawlDelay = true;
            }
        }

        return [
            'userAgents' => array_values(array_unique($userAgents)),
            'groups' => $groups,
            'hasWildcardGroup' => $hasWildcard,
            'hasCrawlDelay' => $hasCrawlDelay,
        ];
    }
}

