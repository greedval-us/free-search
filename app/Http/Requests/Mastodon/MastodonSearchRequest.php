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
        if (! $this->has('resolve')) {
            return;
        }

        $resolve = $this->input('resolve');

        if (is_string($resolve)) {
            $normalized = filter_var($resolve, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

            if ($normalized !== null) {
                $this->merge([
                    'resolve' => $normalized,
                ]);
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
            'q' => ['required', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(['all', 'statuses', 'accounts', 'hashtags'])],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.$this->mastodonModuleConfig()->searchLimitMax()],
            'offset' => ['nullable', 'integer', 'min:0'],
            'resolve' => ['nullable', 'boolean'],
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
        );
    }
}
