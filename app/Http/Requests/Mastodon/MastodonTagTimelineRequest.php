<?php

namespace App\Http\Requests\Mastodon;

use App\Http\Requests\Mastodon\Concerns\ResolvesMastodonModuleConfig;
use Illuminate\Foundation\Http\FormRequest;

final class MastodonTagTimelineRequest extends FormRequest
{
    use ResolvesMastodonModuleConfig;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tagName' => (string) $this->route('tagName', $this->input('tagName', '')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tagName' => ['required', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.$this->mastodonModuleConfig()->searchLimitMax()],
            'maxId' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function tagName(): string
    {
        return trim((string) $this->validated('tagName', ''));
    }

    public function limit(): int
    {
        $config = $this->mastodonModuleConfig();

        return min(
            $config->searchLimitMax(),
            max(1, (int) ($this->validated('limit') ?? $config->searchLimitDefault()))
        );
    }

    public function maxId(): ?string
    {
        $value = trim((string) ($this->validated('maxId') ?? ''));

        return $value === '' ? null : $value;
    }
}
