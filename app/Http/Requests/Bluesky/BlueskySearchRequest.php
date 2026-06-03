<?php

namespace App\Http\Requests\Bluesky;

use App\Http\Requests\Bluesky\Concerns\ResolvesBlueskyModuleConfig;
use App\Modules\Bluesky\DTO\Request\BlueskySearchQueryDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BlueskySearchRequest extends FormRequest
{
    use ResolvesBlueskyModuleConfig;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(['all', 'posts', 'actors'])],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.$this->blueskyModuleConfig()->searchLimitMax()],
            'cursor' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', Rule::in(['top', 'latest'])],
            'language' => ['nullable', 'string', 'max:12'],
            'author' => ['nullable', 'string', 'max:255'],
            'mentions' => ['nullable', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:2048'],
            'tag' => ['nullable', 'string', 'max:64'],
            'since' => ['nullable', 'date'],
            'until' => ['nullable', 'date', 'after_or_equal:since'],
        ];
    }

    public function toDTO(): BlueskySearchQueryDTO
    {
        $validated = $this->validated();
        $config = $this->blueskyModuleConfig();

        return new BlueskySearchQueryDTO(
            query: trim((string) $validated['q']),
            type: (string) ($validated['type'] ?? $config->defaultType()),
            limit: min($config->searchLimitMax(), max(1, (int) ($validated['limit'] ?? $config->searchLimitDefault()))),
            cursor: trim((string) ($validated['cursor'] ?? '')),
            sort: (string) ($validated['sort'] ?? $config->defaultSort()),
            language: strtolower(trim((string) ($validated['language'] ?? ''))),
            author: trim((string) ($validated['author'] ?? '')),
            mentions: ltrim(strtolower(trim((string) ($validated['mentions'] ?? ''))), '@'),
            domain: strtolower(trim((string) ($validated['domain'] ?? ''))),
            url: trim((string) ($validated['url'] ?? '')),
            tag: ltrim(strtolower(trim((string) ($validated['tag'] ?? ''))), '#'),
            since: trim((string) ($validated['since'] ?? '')),
            until: trim((string) ($validated['until'] ?? '')),
        );
    }
}
