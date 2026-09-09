<?php

namespace App\Http\Requests\TelegramBot;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePreferencesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'locale' => ['required', 'in:ru,en'],
            'notifications_enabled' => ['required', 'boolean'],
            'exports_enabled' => ['required', 'boolean'],
            'broadcasts_enabled' => ['required', 'boolean'],
        ];
    }
}
