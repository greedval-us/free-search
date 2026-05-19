<?php

namespace App\Modules\EmailIntel\Application\Contracts;

interface EmailTxtRecordLookupInterface
{
    /**
     * @return array<int, string>
     */
    public function lookup(string $domain): array;
}

