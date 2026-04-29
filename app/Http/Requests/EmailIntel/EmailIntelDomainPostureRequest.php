<?php

namespace App\Http\Requests\EmailIntel;

use App\Http\Requests\LocalizedFormRequest;

class EmailIntelDomainPostureRequest extends LocalizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'domain' => ['required', 'string', 'max:253', 'regex:/^(?!-)(?:[a-z0-9-]{1,63}\.)+[a-z]{2,63}$/i'],
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
