<?php

namespace App\Modules\EmailIntel\Application\Services\EmailIntel;

final class EmailSpfIncludeResolver
{
    private const MAX_INCLUDES = 12;

    public function __construct(
        private readonly EmailSpfParser $spfParser,
    ) {
    }

    /**
     * Resolves first-level SPF include records. The module stays online-only:
     * records are queried live and returned in the response without persistence.
     *
     * @param array<int, string> $includes
     * @return array<int, array<string, mixed>>
     */
    public function resolve(array $includes): array
    {
        $resolved = [];

        foreach (array_slice(array_unique($includes), 0, self::MAX_INCLUDES) as $domain) {
            $txtRecords = $this->txtRecords($domain);
            $parsed = $this->spfParser->parse($txtRecords);

            $resolved[] = [
                'domain' => $domain,
                'resolved' => (bool) $parsed['present'],
                'record' => $parsed['record'],
                'includes' => $parsed['includes'],
                'ip4' => $parsed['ip4'],
                'ip6' => $parsed['ip6'],
                'all' => $parsed['all'],
                'strictness' => $parsed['strictness'],
            ];
        }

        return $resolved;
    }

    /**
     * @return array<int, string>
     */
    private function txtRecords(string $domain): array
    {
        $records = @dns_get_record($domain, DNS_TXT);
        if ($records === false) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (array $record): ?string => isset($record['txt']) ? (string) $record['txt'] : null,
            $records,
        )));
    }
}
