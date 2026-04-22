<?php

namespace App\Modules\Fio\Domain\DTO;

final class PublicSearchEntryDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $snippet,
        public readonly string $url,
        public readonly ?string $domain,
    ) {
    }
}
