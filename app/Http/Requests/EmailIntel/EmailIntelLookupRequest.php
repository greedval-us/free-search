<?php

namespace App\Http\Requests\EmailIntel;

use App\Http\Requests\AbstractLocalizedRequest;

class EmailIntelLookupRequest extends AbstractLocalizedRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'locale' => $this->localeRule(),
        ];
    }

    public function email(): string
    {
        return mb_strtolower(trim((string) $this->validated('email')));
    }
}
