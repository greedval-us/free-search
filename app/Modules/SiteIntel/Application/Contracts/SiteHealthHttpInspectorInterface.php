<?php

namespace App\Modules\SiteIntel\Application\Contracts;

interface SiteHealthHttpInspectorInterface
{
    /**
     * @return array<string, mixed>
     */
    public function inspect(string $url): array;
}

