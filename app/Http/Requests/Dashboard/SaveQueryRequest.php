<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class SaveQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'request_log_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function requestLogId(): int
    {
        return (int) $this->validated('request_log_id');
    }
}

