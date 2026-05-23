<?php

namespace App\Modules\EmailIntel\Application\Contracts;

interface DomainMailPostureServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function inspect(string $domain): array;
}

