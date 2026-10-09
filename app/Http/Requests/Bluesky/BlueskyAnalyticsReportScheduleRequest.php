<?php

namespace App\Http\Requests\Bluesky;

use App\Support\PublicBlueskyAccount;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BlueskyAnalyticsReportScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && ! $this->user()->isBlocked() && $this->user()->hasVerifiedEmail();
    }

    protected function prepareForValidation(): void
    {
        if (is_array($this->input('accounts'))) {
            $this->merge(['accounts' => array_map(
                static fn ($value) => PublicBlueskyAccount::normalize($value) ?? $value,
                $this->input('accounts'),
            )]);
        }
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    public function rules(): array
    {
        if ($this->routeIs('bluesky.analytics.reports.change')) {
            return ['action' => ['required', Rule::in(['pause', 'resume'])]];
        }

        return [
            'name' => ['required', 'string', 'max:100'],
            'accounts' => ['required', 'array', 'list', 'min:1', 'max:'.max(1, (int) config('bluesky_analytics_reports.max_accounts', 3))],
            'accounts.*' => ['bail', 'required', 'string', 'max:255', 'distinct:strict', function (string $attribute, mixed $value, Closure $fail): void {
                if (PublicBlueskyAccount::normalize($value) === null) {
                    $fail(__('bluesky_analytics_reports.errors.invalid_accounts'));
                }
            }],
            'interval' => ['required', 'string', Rule::in(['1', '3', '7', 'month'])],
            'sendTime' => ['required', 'date_format:H:i'],
            'timezone' => ['required', 'timezone'],
            'sendToBot' => ['sometimes', 'boolean'],
        ];
    }

    public function scheduleData(): array
    {
        return [
            'name' => $this->validated('name'),
            'accounts' => $this->validated('accounts'),
            'interval' => $this->validated('interval'),
            'send_time' => $this->validated('sendTime'),
            'timezone' => $this->validated('timezone'),
            'send_to_bot' => (bool) $this->validated('sendToBot', false),
        ];
    }
}
