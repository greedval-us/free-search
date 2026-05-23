<?php

namespace App\Http\Requests\NewsMediaIntel;

use App\Http\Requests\AbstractLocalizedRequest;

class NewsMediaIntelLookupRequest extends AbstractLocalizedRequest
{
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
}
