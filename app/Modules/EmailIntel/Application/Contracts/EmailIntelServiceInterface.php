<?php

namespace App\Modules\EmailIntel\Application\Contracts;

use App\Modules\EmailIntel\Domain\DTO\EmailIntelResultDTO;

interface EmailIntelServiceInterface
{
    public function lookup(string $email): EmailIntelResultDTO;
}

