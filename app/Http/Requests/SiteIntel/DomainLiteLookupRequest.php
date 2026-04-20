<?php

namespace App\Http\Requests\SiteIntel;

use App\Modules\SiteIntel\Support\DomainNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class DomainLiteLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'domain' => ['required', 'string', 'max:255'],
        ];
    }

    public function domain(): string
    {
        return (string) $this->validated('domain');
    }

    public function normalizedDomain(): ?string
    {
        return DomainNormalizer::normalizeDomain($this->domain());
    }
}

