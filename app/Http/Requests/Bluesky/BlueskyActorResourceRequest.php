<?php

namespace App\Http\Requests\Bluesky;

use App\Http\Requests\Bluesky\Concerns\ResolvesBlueskyModuleConfig;
use Illuminate\Foundation\Http\FormRequest;

final class BlueskyActorResourceRequest extends FormRequest
{
    use ResolvesBlueskyModuleConfig;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actor' => ['required', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor' => ['nullable', 'string', 'max:255'],
            'filter' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function actor(): string
    {
        return trim((string) $this->validated('actor'));
    }

    public function limit(): int
    {
        return max(1, min(100, (int) ($this->validated('limit') ?? $this->blueskyModuleConfig()->searchLimitDefault())));
    }

    public function cursor(): ?string
    {
        $value = trim((string) ($this->validated('cursor') ?? ''));

        return $value !== '' ? $value : null;
    }

    public function filter(): ?string
    {
        $value = trim((string) ($this->validated('filter') ?? ''));

        return $value !== '' ? $value : null;
    }
}
