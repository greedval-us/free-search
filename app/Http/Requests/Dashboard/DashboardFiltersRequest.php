<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class DashboardFiltersRequest extends FormRequest
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
            'module_key' => ['nullable', 'string', 'max:64'],
            'query' => ['nullable', 'string', 'max:120'],
            'period' => ['nullable', 'string', 'max:10'],
            'date_from' => ['nullable', 'string', 'max:40'],
            'date_to' => ['nullable', 'string', 'max:40'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return [
            'module_key' => $this->query('module_key'),
            'query' => $this->query('query'),
            'period' => $this->query('period'),
            'date_from' => $this->query('date_from'),
            'date_to' => $this->query('date_to'),
        ];
    }
}
