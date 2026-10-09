<?php

namespace App\Modules\NewsMediaIntel\Application\Services\Marketing;

final class MarketingVisibility
{
    public function build(string $domain, array $sets): array
    {
        $domain = MarketingMentionSet::host(str_contains($domain, '://') ? $domain : 'https://'.$domain);
        $general = $matches = 0;
        $pages = [];
        foreach ($sets as $set) {
            if (! in_array('general', $set['categories'], true)) {
                continue;
            }
            $general++;
            $matched = false;
            foreach ($set['generalLinks'] as $page) {
                $host = MarketingMentionSet::host($page['url']);
                if ($domain !== '' && ($host === $domain || str_ends_with($host, '.'.$domain))) {
                    $matched = true;
                    $existing = $pages[$page['url']] ?? null;
                    if ($existing === null || ($page['position'] !== null && ($existing['position'] === null || $page['position'] < $existing['position']))) {
                        $pages[$page['url']] = $page;
                    }
                }
            }
            $matches += (int) $matched;
        }
        $pages = array_values($pages);
        usort($pages, static fn (array $a, array $b): int => ($a['position'] ?? PHP_INT_MAX) <=> ($b['position'] ?? PHP_INT_MAX));
        $positions = array_filter(array_column($pages, 'position'), static fn ($position): bool => is_int($position));

        return ['domain' => $domain, 'generalResults' => $general, 'domainMatches' => $matches,
            'share' => MarketingMentionSet::percent($matches, $general),
            'bestObservedPosition' => $positions !== [] ? min($positions) : null,
            'pages' => $pages, 'method' => 'observed_general_results'];
    }
}
