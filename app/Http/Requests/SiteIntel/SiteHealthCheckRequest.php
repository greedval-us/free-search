<?php

namespace App\Http\Requests\SiteIntel;

use App\Modules\SiteIntel\Support\DomainNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class SiteHealthCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target' => ['required', 'string', 'max:512'],
        ];
    }

    public function target(): string
    {
        return (string) $this->validated('target');
    }

    public function normalizedUrl(): ?string
    {
        return DomainNormalizer::normalizeUrl($this->target());
    }
}

