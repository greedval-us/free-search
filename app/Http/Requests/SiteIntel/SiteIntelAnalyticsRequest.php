<?php

namespace App\Http\Requests\SiteIntel;

use App\Modules\SiteIntel\Support\DomainNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class SiteIntelAnalyticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target' => ['required', 'string', 'max:512'],
            'locale' => ['nullable', 'string', 'in:ru,en'],
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

    public function normalizedDomain(): ?string
    {
        $domain = DomainNormalizer::normalizeDomain($this->target());
        if ($domain !== null) {
            return $domain;
        }

        $url = $this->normalizedUrl();
        if ($url === null) {
            return null;
        }

        $host = (string) parse_url($url, PHP_URL_HOST);

        return DomainNormalizer::normalizeDomain($host);
    }

    public function locale(): string
    {
        $locale = strtolower(trim((string) ($this->validated('locale') ?? app()->getLocale())));

        return in_array($locale, ['ru', 'en'], true) ? $locale : 'en';
    }
}
