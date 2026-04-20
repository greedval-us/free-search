<?php

namespace App\Http\Requests\Username;

use App\Modules\Username\Domain\DTO\UsernameSearchQueryDTO;
use Illuminate\Foundation\Http\FormRequest;

class UsernameSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'min:2', 'max:39', 'regex:/^[A-Za-z0-9._-]+$/'],
            'locale' => ['nullable', 'string', 'in:ru,en'],
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
        $locale = strtolower(trim((string) ($this->validated('locale') ?? app()->getLocale())));

        return in_array($locale, ['ru', 'en'], true) ? $locale : 'en';
    }
}
