<?php

namespace App\Http\Requests\Telegram;

use App\Rules\PublicTelegramUsername;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TelegramAnalyticsReportScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && ! $this->user()->isBlocked() && $this->user()->hasVerifiedEmail();
    }

    protected function prepareForValidation(): void
    {
        if (is_array($this->input('groups'))) {
            $this->merge(['groups' => array_map(static function ($value) {
                return is_string($value)
                    ? strtolower(preg_replace('~^(?:https?://)?t\.me/(?:s/)?|^@~i', '', rtrim(trim($value), '/')))
                    : $value;
            }, $this->input('groups'))]);
        }
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    public function rules(): array
    {
        if ($this->routeIs('telegram.analytics.reports.change')) {
            return ['action' => ['required', Rule::in(['pause', 'resume'])]];
        }

        return [
            'name' => ['required', 'string', 'max:100'],
            'groups' => ['required', 'array', 'list', 'min:1', 'max:'.max(1, (int) config('telegram_analytics_reports.max_groups', 3))],
            'groups.*' => ['bail', 'required', 'string', 'not_regex:/\s/u', 'distinct', new PublicTelegramUsername],
            'interval' => ['required', 'string', Rule::in(['1', '3', '7', 'month'])],
            'sendTime' => ['required', 'date_format:H:i'],
            'timezone' => ['required', 'timezone'],
            'sendToBot' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['groups.*.not_regex' => __('telegram_analytics_reports.validation.groups_one_per_line')];
    }

    public function scheduleData(): array
    {
        return [
            'name' => $this->validated('name'),
            'groups' => $this->validated('groups'),
            'interval' => $this->validated('interval'),
            'send_time' => $this->validated('sendTime'),
            'timezone' => $this->validated('timezone'),
            'send_to_bot' => (bool) $this->validated('sendToBot', false),
        ];
    }
}
