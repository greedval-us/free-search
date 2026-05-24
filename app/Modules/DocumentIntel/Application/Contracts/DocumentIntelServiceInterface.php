<?php

namespace App\Modules\DocumentIntel\Application\Contracts;

interface DocumentIntelServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function lookup(string $query, ?string $domain): array;
}

