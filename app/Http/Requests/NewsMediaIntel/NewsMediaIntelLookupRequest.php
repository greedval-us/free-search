<?php

namespace App\Http\Requests\NewsMediaIntel;

use App\Http\Requests\LocalizedFormRequest;

class NewsMediaIntelLookupRequest extends LocalizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'min:2', 'max:180'],
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
}

