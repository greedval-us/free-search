<?php

namespace App\Http\Requests\Username;

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
        ];
    }

    public function username(): string
    {
        return ltrim(trim((string) $this->validated('username')), '@');
    }
}
