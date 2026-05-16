<?php

namespace App\Http\Requests\DomainInfraIntel;

use App\Http\Requests\LocalizedFormRequest;

class DomainInfraIntelLookupRequest extends LocalizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'domain' => ['required', 'string', 'min:3', 'max:253'],
            'locale' => $this->localeRule(),
        ];
    }

    public function domain(): string
    {
        return mb_strtolower(trim((string) $this->validated('domain')));
    }

    public function locale(): string
    {
        return $this->resolveLocale();
    }
}

