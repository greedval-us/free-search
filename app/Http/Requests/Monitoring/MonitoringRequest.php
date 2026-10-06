<?php

namespace App\Http\Requests\Monitoring;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class MonitoringRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && ! $this->user()->isBlocked() && $this->user()->hasVerifiedEmail();
    }

    protected function prepareForValidation(): void
    {
        foreach (['name', 'input'] as $key) {
            if (is_string($this->input($key))) {
                $this->merge([$key => trim($this->input($key))]);
            }
        }
    }

    public function rules(): array
    {
        if ($this->routeIs('monitoring.sources.store')) {
            return ['platform' => ['required', Rule::in(['telegram', 'youtube', 'bluesky', 'mastodon', 'news'])],
                'input' => ['required', 'string', 'max:2048']];
        }
        if ($this->routeIs('monitoring.projects.lifecycle')) {
            return ['status' => ['required', Rule::in(['active', 'paused', 'archived'])]];
        }
        if ($this->routeIs('monitoring.projects.destroy')) {
            return ['confirmation' => ['required', Rule::in(['delete'])]];
        }
        if ($this->routeIs('monitoring.reports.store', 'monitoring.reports.regenerate')) {
            return ['period' => ['required', Rule::in(['day', 'three_days', 'week', 'month'])],
                'request_key' => ['required', 'uuid']];
        }
        if ($this->routeIs('monitoring.schedules.store', 'monitoring.schedules.update')) {
            return ['period' => ['required', Rule::in(['day', 'three_days', 'week', 'month'])],
                'enabled' => ['required', 'boolean'], 'delivery_enabled' => ['required', 'boolean'],
                'time' => ['required', 'date_format:H:i'], 'timezone' => ['required', 'timezone:all'],
                'anchor_date' => ['required_if:period,three_days', 'date_format:Y-m-d']];
        }

        return ['name' => ['required', 'string', 'max:100'], 'mode' => ['required', Rule::in(['overview', 'topic'])],
            'filters' => ['required', 'array:include,exclude,author'],
            'filters.include' => ['present', 'array', 'list', 'max:20', Rule::requiredIf($this->input('mode') === 'topic')],
            'filters.include.*' => ['required', 'string', 'min:2', 'max:100', 'distinct'],
            'filters.exclude' => ['present', 'array', 'list', 'max:20'],
            'filters.exclude.*' => ['required', 'string', 'min:2', 'max:100', 'distinct'],
            'filters.author' => ['nullable', 'string', 'regex:/^[1-9][0-9]{0,18}$/'],
            'language' => ['required', Rule::in(['ru', 'en'])], 'timezone' => ['required', 'timezone:all'],
            'delivery_enabled' => ['required', 'boolean'], 'attach_files' => ['required', 'boolean'],
            'empty_delivery' => ['required', Rule::in(['send', 'skip'])], 'collection_enabled' => ['required', 'boolean'],
            'collect_interval_minutes' => ['required', 'integer', 'min:15', 'max:10080']];
    }
}
