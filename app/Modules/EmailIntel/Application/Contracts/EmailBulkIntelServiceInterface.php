<?php

namespace App\Modules\EmailIntel\Application\Contracts;

interface EmailBulkIntelServiceInterface
{
    /**
     * @param array<int, string> $emails
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public function lookup(array $emails): array;
}

