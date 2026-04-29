<?php

namespace App\Http\Requests\EmailIntel;

use App\Http\Requests\LocalizedFormRequest;

class EmailIntelLookupRequest extends LocalizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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

    public function locale(): string
    {
        return $this->resolveLocale();
    }
}
