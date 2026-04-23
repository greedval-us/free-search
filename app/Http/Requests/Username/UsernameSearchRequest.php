<?php

namespace App\Http\Requests\Username;

use App\Http\Requests\LocalizedFormRequest;
use App\Modules\Username\Domain\DTO\UsernameSearchQueryDTO;

class UsernameSearchRequest extends LocalizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'min:2', 'max:39', 'regex:/^[A-Za-z0-9._-]+$/'],
            'locale' => $this->localeRule(),
        ];
    }

    public function username(): string
    {
        return ltrim(trim((string) $this->validated('username')), '@');
    }

    public function toQueryDTO(): UsernameSearchQueryDTO
    {
        return new UsernameSearchQueryDTO(
            username: $this->username(),
        );
    }

    public function locale(): string
    {
        return $this->resolveLocale();
    }
}
