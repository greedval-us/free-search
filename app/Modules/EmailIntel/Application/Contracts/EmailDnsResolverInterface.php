<?php

namespace App\Modules\EmailIntel\Application\Contracts;

interface EmailDnsResolverInterface
{
    /**
     * @return array<string, mixed>
     */
    public function resolve(string $domain): array;
}

