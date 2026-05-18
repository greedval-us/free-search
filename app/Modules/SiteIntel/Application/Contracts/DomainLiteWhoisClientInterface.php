<?php

namespace App\Modules\SiteIntel\Application\Contracts;

interface DomainLiteWhoisClientInterface
{
    public function query(string $server, string $domain): ?string;
}

