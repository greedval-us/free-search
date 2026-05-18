<?php

namespace App\Modules\DocumentIntel\Application\Contracts;

interface DocumentMetadataExtractorInterface
{
    /**
     * @return array<string, mixed>
     */
    public function extract(string $url): array;
}

