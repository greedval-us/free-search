<?php

namespace App\Modules\EmailIntel\Infrastructure\Clients;

use App\Modules\EmailIntel\Application\Contracts\EmailTxtRecordLookupInterface;

final class EmailTxtRecordLookup implements EmailTxtRecordLookupInterface
{
    public function lookup(string $domain): array
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

