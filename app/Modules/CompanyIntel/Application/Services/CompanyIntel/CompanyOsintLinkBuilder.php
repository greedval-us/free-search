<?php

namespace App\Modules\CompanyIntel\Application\Services\CompanyIntel;

use App\Modules\CompanyIntel\Application\Support\CompanyIntelConfig;

final class CompanyOsintLinkBuilder
{
    public function __construct(private readonly CompanyIntelConfig $config)
    {
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    public function build(string $query, ?string $domain): array
    {
        $links = [];

        foreach ($this->globalLinkTemplates() as $label => $template) {
            $url = strtr($template, [
                '{query}' => rawurlencode($query),
                '{query_jobs}' => rawurlencode($query . ' jobs careers'),
                '{query_linkedin}' => rawurlencode($query . ' site:linkedin.com/company'),
                '{query_glassdoor}' => rawurlencode($query . ' site:glassdoor.com'),
                '{query_crunchbase}' => rawurlencode($query . ' site:crunchbase.com/organization'),
                '{query_x}' => rawurlencode($query . ' site:x.com'),
                '{query_youtube}' => rawurlencode($query . ' site:youtube.com'),
                '{query_medium}' => rawurlencode($query . ' site:medium.com'),
                '{query_paste}' => rawurlencode($query . ' (paste OR leak OR dump)'),
            ]);

            $links[] = [
                'label' => $label,
                'url' => $url,
            ];
        }

        if ($domain !== null) {
            foreach ($this->domainLinkTemplates() as $label => $template) {
                $links[] = [
                    'label' => $label,
                    'url' => strtr($template, [
                        '{domain}' => rawurlencode($domain),
                    ]),
                ];
            }
        }

        return $links;
    }

    /**
     * @return array<string, string>
     */
    private function globalLinkTemplates(): array
    {
        return $this->config->globalLinkTemplates();
    }

    /**
     * @return array<string, string>
     */
    private function domainLinkTemplates(): array
    {
        return $this->config->domainLinkTemplates();
    }
}
