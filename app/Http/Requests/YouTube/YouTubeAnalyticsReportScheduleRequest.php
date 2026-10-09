<?php

namespace App\Http\Requests\YouTube;

use App\Modules\YouTube\Analytics\Reports\PublicYouTubeChannel;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class YouTubeAnalyticsReportScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && ! $this->user()->isBlocked() && $this->user()->hasVerifiedEmail();
    }

    protected function prepareForValidation(): void
    {
        if (is_array($this->input('channels'))) {
            $this->merge(['channels' => array_map(
                static fn ($value) => PublicYouTubeChannel::normalize($value) ?? $value,
                $this->input('channels'),
            )]);
        }
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    public function rules(): array
    {
        if ($this->routeIs('youtube.analytics.reports.change')) {
            return ['action' => ['required', Rule::in(['pause', 'resume'])]];
        }

        return [
            'name' => ['required', 'string', 'max:100'],
            'channels' => ['required', 'array', 'list', 'min:1', 'max:'.max(1, (int) config('youtube_analytics_reports.max_channels', 3))],
            'channels.*' => ['bail', 'required', 'string', 'max:128', 'distinct:strict', function (string $attribute, mixed $value, Closure $fail): void {
                if (PublicYouTubeChannel::normalize($value) === null) {
                    $fail(__('youtube_analytics_reports.errors.invalid_channels'));
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
            'channels' => $this->validated('channels'),
            'interval' => $this->validated('interval'),
            'send_time' => $this->validated('sendTime'),
            'timezone' => $this->validated('timezone'),
            'send_to_bot' => (bool) $this->validated('sendToBot', false),
        ];
    }
}
