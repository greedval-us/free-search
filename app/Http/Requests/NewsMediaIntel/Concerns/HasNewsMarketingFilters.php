<?php

namespace App\Http\Requests\NewsMediaIntel\Concerns;

use App\Support\Domains\PublicSiteTarget;
use Closure;
use Illuminate\Validation\Validator;

trait HasNewsMarketingFilters
{
    protected function prepareMarketingFilters(): void
    {
        foreach (['brand', 'domain'] as $key) {
            if (is_string($this->input($key))) {
                $this->merge([$key => trim($this->input($key))]);
            }
        }
        if (is_array($this->input('competitors'))) {
            $this->merge(['competitors' => array_map(static fn (mixed $value): mixed => is_string($value) ? trim($value) : $value, $this->input('competitors'))]);
        }
    }

    protected function marketingFilterRules(): array
    {
        return [
            'brand' => ['nullable', 'string', 'min:2', 'max:80', 'not_regex:/[\p{Cc}]/u'],
            'competitors' => ['sometimes', 'array', 'list', 'max:3'],
            'competitors.*' => ['required', 'string', 'min:2', 'max:80', 'distinct:ignore_case', 'not_regex:/[\p{Cc}]/u'],
            'domain' => ['nullable', 'string', 'max:253', function (string $attribute, mixed $value, Closure $fail): void {
                if ($value !== null && $value !== '' && PublicSiteTarget::normalize($value) === null) {
                    $fail(__('news_media_intel.errors.domain'));
                }
            }],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['brand', 'competitors', 'competitors.*'])) {
                return;
            }
            $brand = mb_strtolower((string) $this->input('brand', ''));
            foreach ((array) $this->input('competitors', []) as $competitor) {
                if (is_string($competitor) && mb_strtolower($competitor) === $brand) {
                    $validator->errors()->add('competitors', __('news_media_intel.errors.duplicate_brand'));
                }
            }
        }];
    }

    protected function marketingDomain(): string
    {
        $domain = $this->validated('domain');
        $url = is_string($domain) && $domain !== '' ? PublicSiteTarget::normalize($domain) : null;

        return $url === null ? '' : (string) parse_url($url, PHP_URL_HOST);
    }
}
