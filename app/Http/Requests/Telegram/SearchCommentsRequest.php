<?php

namespace App\Http\Requests\Telegram;

use Illuminate\Foundation\Http\FormRequest;

class SearchCommentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chatUsername' => ['required', 'string', 'max:255'],
            'postId' => ['required', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'offsetId' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function chatUsername(): string
    {
        return ltrim(trim((string) $this->validated('chatUsername')), '@');
    }

    public function postId(): int
    {
        return (int) $this->validated('postId');
    }

    public function limitValue(): int
    {
        return (int) ($this->validated('limit') ?? 20);
    }

    public function offsetId(): int
    {
        return (int) ($this->validated('offsetId') ?? 0);
    }
}
