<?php

namespace App\Modules\NewsMediaIntel\Monitoring;

use App\Modules\NewsMediaIntel\Domain\DTO\NewsMentionDTO;

final readonly class NewsFeedPage
{
    /** @param list<NewsMentionDTO> $items */
    public function __construct(public array $items, public bool $complete = false, public array $warnings = []) {}
}
