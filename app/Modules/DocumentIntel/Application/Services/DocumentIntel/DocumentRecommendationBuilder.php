<?php

namespace App\Modules\DocumentIntel\Application\Services\DocumentIntel;

final class DocumentRecommendationBuilder
{
    /**
     * @param array<int, string> $signals
     * @return array<int, string>
     */
    public function build(array $signals): array
    {
        $recommendations = [];

        if (in_array('domain_not_detected', $signals, true)) {
            $recommendations[] = 'specify_company_domain';
        }
        if (in_array('no_dns_resolution', $signals, true)) {
            $recommendations[] = 'validate_domain_and_dns';
        }
        if (in_array('missing_spf', $signals, true)) {
            $recommendations[] = 'configure_spf';
        }
        if (in_array('missing_dmarc', $signals, true)) {
            $recommendations[] = 'configure_dmarc';
        }
        if (in_array('whois_unavailable', $signals, true)) {
            $recommendations[] = 'check_whois_visibility';
        }
        if (in_array('baseline_ok', $signals, true)) {
            $recommendations[] = 'start_document_collection';
            $recommendations[] = 'review_document_metadata';
        }

        return array_values(array_unique($recommendations));
    }
}
