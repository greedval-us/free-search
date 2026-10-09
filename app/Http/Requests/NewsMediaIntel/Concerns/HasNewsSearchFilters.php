<?php

namespace App\Http\Requests\NewsMediaIntel\Concerns;

use App\Modules\NewsMediaIntel\Application\Support\NewsMediaIntelConfig;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsSearchOptionsDTO;
use Closure;
use Illuminate\Validation\Rule;

trait HasNewsSearchFilters
{
    protected function searchFilterRules(): array
    {
        $engines = array_merge(...array_values((array) config('osint.news_media_intel.searxng.available_engines', [])));

        return [
            'query' => ['bail', 'required', 'string', 'min:2', 'max:180', function (string $attribute, mixed $value, Closure $fail): void {
                if (preg_match('/(?:^|\s)[!:][^\s]+|[\p{Cc}]/u', $value) !== 0) {
                    $fail(__('news_media_intel.errors.query_routing'));
                }
            }],
            'categories' => ['sometimes', 'array', 'list', 'min:1', 'max:2'],
            'categories.*' => ['required', 'string', 'distinct', Rule::in(['news', 'general'])],
            'language' => ['sometimes', 'string', Rule::in(config('osint.news_media_intel.searxng.languages', ['all', 'ru', 'en']))],
            'timeRange' => ['nullable', Rule::in(['', 'day', 'week', 'month', 'year'])],
            'safeSearch' => ['sometimes', 'integer', Rule::in([0, 1, 2])],
            'engines' => ['sometimes', 'array', 'list', 'max:8'],
            'engines.*' => ['required', 'string', 'distinct', Rule::in($engines)],
            'maxPages' => ['sometimes', 'integer', 'min:1', 'max:'.app(NewsMediaIntelConfig::class)->searxngMaxPages()],
            'locale' => $this->localeRule(),
        ];
    }

    protected function prepareSearchFilters(): void
    {
        if (is_string($this->input('query'))) {
            $this->merge(['query' => trim($this->input('query'))]);
        }
    }

    public function searchOptions(): NewsSearchOptionsDTO
    {
        $config = app(NewsMediaIntelConfig::class);

        return new NewsSearchOptionsDTO(
            categories: $this->validated('categories', ['news']),
            language: $this->validated('language', $config->searxngLanguage()),
            timeRange: array_key_exists('timeRange', $this->validated()) ? (string) $this->validated('timeRange') : $config->searxngTimeRange(),
            safeSearch: (int) $this->validated('safeSearch', $config->searxngSafeSearch()),
            engines: $this->validated('engines', []),
            maxPages: (int) $this->validated('maxPages', $config->searxngMaxPages()),
        );
    }
}
