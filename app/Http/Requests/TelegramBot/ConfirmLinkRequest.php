<?php

namespace App\Http\Requests\TelegramBot;

use Illuminate\Foundation\Http\FormRequest;

final class ConfirmLinkRequest extends FormRequest
{
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid']];
    }
}
