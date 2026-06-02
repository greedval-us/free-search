<?php

namespace App\Http\Requests\Mastodon;

use App\Http\Requests\Mastodon\Concerns\ResolvesMastodonModuleConfig;
use App\Modules\Mastodon\DTO\Request\MastodonSearchQueryDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class MastodonSearchRequest extends FormRequest
{
    use ResolvesMastodonModuleConfig;

    protected function prepareForValidation(): void
    {
        foreach (['resolve', 'hasMedia', 'hasLinks', 'hasReplies'] as $key) {
            if (! $this->has($key)) {
                continue;
            }

            $value = $this->input($key);

            if (! is_string($value)) {
                continue;
            }

            $normalized = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

            if ($normalized === null) {
                continue;
            }

            $this->merge([
                $key => $normalized,
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(['all', 'statuses', 'accounts', 'hashtags'])],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.$this->mastodonModuleConfig()->searchLimitMax()],
            'offset' => ['nullable', 'integer', 'min:0'],
            'resolve' => ['nullable', 'boolean'],
            'language' => ['nullable', 'string', 'max:12'],
            'hasMedia' => ['nullable', 'boolean'],
            'hasLinks' => ['nullable', 'boolean'],
            'hasReplies' => ['nullable', 'boolean'],
            'author' => ['nullable', 'string', 'max:255'],
            'dateFrom' => ['nullable', 'date'],
            'dateTo' => ['nullable', 'date', 'after_or_equal:dateFrom'],
            'instanceDomain' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function toDTO(): MastodonSearchQueryDTO
    {
        $validated = $this->validated();
        $config = $this->mastodonModuleConfig();

        return new MastodonSearchQueryDTO(
            query: trim((string) $validated['q']),
            type: (string) ($validated['type'] ?? $config->defaultType()),
            limit: min($config->searchLimitMax(), max(1, (int) ($validated['limit'] ?? $config->searchLimitDefault()))),
            offset: max(0, (int) ($validated['offset'] ?? 0)),
            resolve: filter_var($validated['resolve'] ?? false, FILTER_VALIDATE_BOOL),
            language: strtolower(trim((string) ($validated['language'] ?? ''))),
            hasMedia: array_key_exists('hasMedia', $validated)
                ? filter_var($validated['hasMedia'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)
                : null,
            hasLinks: array_key_exists('hasLinks', $validated)
                ? filter_var($validated['hasLinks'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)
                : null,
            hasReplies: array_key_exists('hasReplies', $validated)
                ? filter_var($validated['hasReplies'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)
                : null,
            author: trim((string) ($validated['author'] ?? '')),
            dateFrom: trim((string) ($validated['dateFrom'] ?? '')),
            dateTo: trim((string) ($validated['dateTo'] ?? '')),
            instanceDomain: strtolower(trim((string) ($validated['instanceDomain'] ?? ''))),
        );
    }
}
