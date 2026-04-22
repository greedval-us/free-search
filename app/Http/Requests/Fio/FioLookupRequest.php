<?php

namespace App\Http\Requests\Fio;

use Illuminate\Foundation\Http\FormRequest;

class FioLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:3', 'max:120', 'regex:/^[\p{L}\p{M}\s\'\-.]+$/u'],
            'locale' => ['nullable', 'string', 'in:ru,en'],
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
        $locale = strtolower(trim((string) ($this->validated('locale') ?? app()->getLocale())));

        return in_array($locale, ['ru', 'en'], true) ? $locale : 'en';
    }
}
