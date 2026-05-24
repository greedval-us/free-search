<?php

namespace App\Modules\EmailIntel\Application\Services\EmailIntel;

use App\Modules\EmailIntel\Application\Contracts\DomainMailPostureServiceInterface;

final class DomainMailPostureService implements DomainMailPostureServiceInterface
{
    public function __construct(
        private readonly EmailDomainIntelAssembler $domainIntelAssembler,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function inspect(string $domain): array
    {
        return $this->domainIntelAssembler->assemble($domain);
    }
}
