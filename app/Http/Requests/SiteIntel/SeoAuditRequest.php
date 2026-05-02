<?php

namespace App\Http\Requests\SiteIntel;

use App\Http\Requests\LocalizedFormRequest;
use App\Modules\SiteIntel\Support\DomainNormalizer;

class SeoAuditRequest extends LocalizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target' => ['required', 'string', 'max:512'],
            'crawl_limit' => ['nullable', 'integer', 'min:3', 'max:20'],
            'platform_type' => ['nullable', 'string', 'in:auto,generic,media-platform,content-site,storefront'],
            'locale' => $this->localeRule(),
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

    public function crawlLimit(): int
    {
        return (int) ($this->validated('crawl_limit') ?? 8);
    }

    public function locale(): string
    {
        return $this->resolveLocale();
    }

    public function platformType(): ?string
    {
        $value = (string) ($this->validated('platform_type') ?? 'auto');

        if ($value === '' || $value === 'auto') {
            return null;
        }

        return $value;
    }
}
