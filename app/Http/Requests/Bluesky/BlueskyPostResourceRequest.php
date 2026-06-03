<?php

namespace App\Http\Requests\Bluesky;

use App\Http\Requests\Bluesky\Concerns\ResolvesBlueskyModuleConfig;
use Illuminate\Foundation\Http\FormRequest;

final class BlueskyPostResourceRequest extends FormRequest
{
    use ResolvesBlueskyModuleConfig;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uri' => ['required', 'string', 'max:255'],
            'cid' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.$this->blueskyModuleConfig()->searchLimitMax()],
            'cursor' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function uri(): string
    {
        return trim((string) $this->validated('uri'));
    }

    public function cid(): ?string
    {
        $value = trim((string) ($this->validated('cid') ?? ''));

        return $value !== '' ? $value : null;
    }

    public function limit(): int
    {
        $config = $this->blueskyModuleConfig();

        return min(
            $config->searchLimitMax(),
            max(1, (int) ($this->validated('limit') ?? $config->searchLimitDefault()))
        );
    }

    public function cursor(): ?string
    {
        $value = trim((string) ($this->validated('cursor') ?? ''));

        return $value !== '' ? $value : null;
    }
}
