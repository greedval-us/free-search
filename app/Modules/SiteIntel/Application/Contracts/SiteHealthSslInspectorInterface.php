<?php

namespace App\Modules\SiteIntel\Application\Contracts;

interface SiteHealthSslInspectorInterface
{
    /**
     * @return array<string, mixed>
     */
    public function inspect(string $url): array;
}
