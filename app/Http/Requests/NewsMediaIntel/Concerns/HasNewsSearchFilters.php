<?php

namespace App\Http\Requests\NewsMediaIntel\Concerns;

use App\Modules\NewsMediaIntel\Application\Support\NewsMediaIntelConfig;
use App\Modules\NewsMediaIntel\Application\Support\NewsSearchInputPolicy;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsSearchOptionsDTO;
use Closure;
use Illuminate\Validation\Rule;

trait HasNewsSearchFilters
{
    protected function searchFilterRules(): array
    {
        $engines = array_merge(...array_values((array) config('osint.news_media_intel.searxng.available_engines', [])));

        return [
            'query' => ['bail', 'required', 'string', 'min:'.NewsSearchInputPolicy::MIN_TEXT_LENGTH, 'max:'.NewsSearchInputPolicy::MAX_QUERY_LENGTH, function (string $attribute, mixed $value, Closure $fail): void {
                if (NewsSearchInputPolicy::hasForbiddenQuerySyntax($value)) {
                    $fail(__('news_media_intel.errors.query_routing'));
                }
            }],
            'categories' => ['sometimes', 'array', 'list', 'min:1', 'max:'.count(NewsSearchInputPolicy::CATEGORIES)],
            'categories.*' => ['required', 'string', 'distinct', Rule::in(NewsSearchInputPolicy::CATEGORIES)],
            'language' => ['sometimes', 'string', Rule::in(config('osint.news_media_intel.searxng.languages', ['all', 'ru', 'en']))],
            'timeRange' => ['nullable', Rule::in(NewsSearchInputPolicy::TIME_RANGES)],
            'safeSearch' => ['sometimes', 'integer', Rule::in(NewsSearchInputPolicy::SAFE_SEARCH_LEVELS)],
            'engines' => ['sometimes', 'array', 'list', 'max:'.NewsSearchInputPolicy::MAX_ENGINES],
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
