<?php

namespace App\Http\Requests\Dashboard;

use App\Support\Dashboard\DashboardModuleRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ToggleModulePinRequest extends FormRequest
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
            'module_key' => ['required', 'string', Rule::in($this->moduleRegistry()->keys())],
        ];
    }

    public function moduleKey(): string
    {
        return (string) $this->validated('module_key');
    }

    private function moduleRegistry(): DashboardModuleRegistry
    {
        /** @var DashboardModuleRegistry $registry */
        $registry = app(DashboardModuleRegistry::class);

        return $registry;
    }
}

