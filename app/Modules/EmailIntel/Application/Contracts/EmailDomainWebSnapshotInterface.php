<?php

namespace App\Modules\EmailIntel\Application\Contracts;

interface EmailDomainWebSnapshotInterface
{
    /**
     * @return array<string, mixed>
     */
    public function inspect(string $domain): array;
}

