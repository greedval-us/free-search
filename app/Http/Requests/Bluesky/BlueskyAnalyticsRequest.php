<?php

namespace App\Http\Requests\Bluesky;

use App\Http\Requests\Concerns\NormalizesBooleanInputs;
use App\Http\Requests\LocalizedFormRequest;
use App\Http\Requests\Bluesky\Concerns\ResolvesBlueskyModuleConfig;
use App\Modules\Bluesky\DTO\Request\BlueskyAnalyticsQueryDTO;
use Illuminate\Validation\Rule;

final class BlueskyAnalyticsRequest extends LocalizedFormRequest
{
    use NormalizesBooleanInputs;
    use ResolvesBlueskyModuleConfig;

    protected function prepareForValidation(): void
    {
        $this->normalizeBooleanInputs(['resolve']);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mode' => ['nullable', Rule::in(['account', 'hashtag'])],
            'target' => ['required', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.$this->blueskyModuleConfig()->searchLimitMax()],
            'pages' => ['nullable', 'integer', 'min:1', 'max:5'],
            'dateFrom' => ['nullable', 'date_format:Y-m-d'],
            'dateTo' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dateFrom'],
            'resolve' => ['nullable', 'boolean'],
            'locale' => $this->localeRule(),
        ];
    }

    public function toDTO(): BlueskyAnalyticsQueryDTO
    {
        $validated = $this->validated();
        $config = $this->blueskyModuleConfig();

        return new BlueskyAnalyticsQueryDTO(
            mode: (string) ($validated['mode'] ?? 'account'),
            target: trim((string) $validated['target']),
            limit: min($config->searchLimitMax(), max(1, (int) ($validated['limit'] ?? $config->searchLimitDefault()))),
            pages: min(5, max(1, (int) ($validated['pages'] ?? 3))),
            dateFrom: trim((string) ($validated['dateFrom'] ?? '')),
            dateTo: trim((string) ($validated['dateTo'] ?? '')),
            resolve: array_key_exists('resolve', $validated)
                ? filter_var($validated['resolve'], FILTER_VALIDATE_BOOL)
                : true,
        );
    }

    public function locale(): string
    {
        return $this->resolveLocale();
    }
}
