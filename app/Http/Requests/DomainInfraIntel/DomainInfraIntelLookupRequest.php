<?php

namespace App\Http\Requests\DomainInfraIntel;

use App\Http\Requests\AbstractLocalizedRequest;

class DomainInfraIntelLookupRequest extends AbstractLocalizedRequest
{
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
}
