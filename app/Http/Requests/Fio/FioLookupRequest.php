<?php

namespace App\Http\Requests\Fio;

use App\Http\Requests\LocalizedFormRequest;

class FioLookupRequest extends LocalizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:3', 'max:120', 'regex:/^[\p{L}\p{M}\s\'\-.]+$/u'],
            'qualifier' => ['nullable', 'string', 'min:2', 'max:64', 'regex:/^[\p{L}\p{M}\s\'\-.]+$/u'],
            'locale' => $this->localeRule(),
        ];
    }

    public function fullName(): string
    {
        $value = trim((string) $this->validated('full_name'));
        $value = preg_replace('/\s+/u', ' ', $value);

        return is_string($value) ? $value : '';
    }

    public function locale(): string
    {
        return $this->resolveLocale();
    }

    public function qualifier(): ?string
    {
        $value = $this->validated('qualifier');
        if (!is_string($value)) {
            return null;
        }

        $normalized = trim($value);
        $normalized = preg_replace('/\s+/u', ' ', $normalized);

        if (!is_string($normalized) || $normalized === '') {
            return null;
        }

        return $normalized;
    }
}
