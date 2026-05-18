<?php

namespace App\Modules\DocumentIntel\Application\Contracts;

interface DocumentUrlCollectorInterface
{
    /**
     * @return array<int, string>
     */
    public function collect(string $domain): array;
}

