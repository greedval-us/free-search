<?php

namespace App\Http\Requests\Mastodon;

use App\Http\Requests\Mastodon\Concerns\ResolvesMastodonModuleConfig;
use App\Modules\Mastodon\DTO\Request\MastodonAnalyticsQueryDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class MastodonAnalyticsRequest extends FormRequest
{
    use ResolvesMastodonModuleConfig;

    protected function prepareForValidation(): void
    {
        if ($this->has('resolve') && is_string($this->input('resolve'))) {
            $normalized = filter_var($this->input('resolve'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

            if ($normalized !== null) {
                $this->merge(['resolve' => $normalized]);
            }
        }
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
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.$this->mastodonModuleConfig()->searchLimitMax()],
            'pages' => ['nullable', 'integer', 'min:1', 'max:5'],
            'dateFrom' => ['nullable', 'date_format:Y-m-d'],
            'dateTo' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dateFrom'],
            'resolve' => ['nullable', 'boolean'],
        ];
    }

    public function toDTO(): MastodonAnalyticsQueryDTO
    {
        $validated = $this->validated();
        $config = $this->mastodonModuleConfig();

        return new MastodonAnalyticsQueryDTO(
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
}
