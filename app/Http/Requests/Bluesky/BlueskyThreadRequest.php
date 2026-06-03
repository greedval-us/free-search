<?php

namespace App\Http\Requests\Bluesky;

use Illuminate\Foundation\Http\FormRequest;

final class BlueskyThreadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uri' => ['required', 'string', 'max:255'],
            'depth' => ['nullable', 'integer', 'min:0', 'max:20'],
            'parentHeight' => ['nullable', 'integer', 'min:0', 'max:20'],
        ];
    }

    public function uri(): string
    {
        return trim((string) $this->validated('uri'));
    }

    public function depth(): int
    {
        return max(0, min(20, (int) ($this->validated('depth') ?? 6)));
    }

    public function parentHeight(): int
    {
        return max(0, min(20, (int) ($this->validated('parentHeight') ?? 6)));
    }
}
