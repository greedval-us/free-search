<?php

namespace App\Modules\EmailIntel\Application\Services\EmailIntel;

final class DomainMailPostureService
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
