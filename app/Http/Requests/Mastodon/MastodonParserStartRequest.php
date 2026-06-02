<?php

namespace App\Http\Requests\Mastodon;

use App\Modules\Mastodon\DTO\Request\MastodonParserStartDTO;
use Illuminate\Foundation\Http\FormRequest;

final class MastodonParserStartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account' => ['required', 'string', 'max:255'],
        ];
    }

    public function toStartDTO(): MastodonParserStartDTO
    {
        return new MastodonParserStartDTO(
            userId: (int) $this->user()->id,
            account: $this->normalizedAccount(),
        );
    }

    private function normalizedAccount(): string
    {
        return ltrim(trim((string) $this->validated('account')), '@');
    }
}
