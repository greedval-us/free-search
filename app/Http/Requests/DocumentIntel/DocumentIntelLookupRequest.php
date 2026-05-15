<?php

namespace App\Http\Requests\DocumentIntel;

use App\Http\Requests\LocalizedFormRequest;
use App\Modules\SiteIntel\Support\DomainNormalizer;

class DocumentIntelLookupRequest extends LocalizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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

    public function locale(): string
    {
        return $this->resolveLocale();
    }

    public function normalizedDomain(): ?string
    {
        return DomainNormalizer::normalizeDomain($this->searchQuery());
    }
}
