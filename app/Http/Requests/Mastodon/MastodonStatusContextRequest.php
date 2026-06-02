<?php

namespace App\Http\Requests\Mastodon;

use Illuminate\Foundation\Http\FormRequest;

final class MastodonStatusContextRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'statusId' => (string) $this->route('statusId', ''),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'statusId' => ['required', 'string', 'regex:/^\d+$/'],
        ];
    }

    public function statusId(): string
    {
        return (string) $this->route('statusId', '');
    }
}
