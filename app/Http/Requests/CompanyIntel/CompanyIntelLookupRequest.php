<?php

namespace App\Http\Requests\CompanyIntel;

use App\Http\Requests\AbstractLocalizedRequest;
use App\Modules\SiteIntel\Support\DomainNormalizer;

class CompanyIntelLookupRequest extends AbstractLocalizedRequest
{
    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'min:2', 'max:255'],
            'locale' => $this->localeRule(),
        ];
    }

    public function searchQuery(): string
    {
        return trim((string) $this->validated('query'));
    }

    public function normalizedDomain(): ?string
    {
        return DomainNormalizer::normalizeDomain($this->searchQuery());
    }
}
