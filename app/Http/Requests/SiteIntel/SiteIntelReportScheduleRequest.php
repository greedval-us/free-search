<?php

namespace App\Http\Requests\SiteIntel;

use App\Modules\SiteIntel\Application\Reports\PublicSiteTarget;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SiteIntelReportScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && ! $this->user()->isBlocked() && $this->user()->hasVerifiedEmail();
    }

    protected function prepareForValidation(): void
    {
        if (is_array($this->input('targets'))) {
            $this->merge(['targets' => array_map(static fn ($value) => PublicSiteTarget::normalize($value) ?? $value, $this->input('targets'))]);
        }
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    public function rules(): array
    {
        if ($this->routeIs('site-intel.reports.change')) {
            return ['action' => ['required', Rule::in(['pause', 'resume'])]];
        }

        return [
            'name' => ['required', 'string', 'max:100'],
            'targets' => ['required', 'array', 'list', 'min:1', 'max:'.max(1, (int) config('site_intel_reports.max_targets', 3))],
            'targets.*' => ['bail', 'required', 'string', 'max:512', 'distinct:strict', function (string $attribute, mixed $value, Closure $fail): void {
                if (PublicSiteTarget::normalize($value) === null) {
                    $fail(__('site_intel_reports.errors.invalid_targets'));
                }
            }],
            'reportType' => ['required', 'string', Rule::in(['analytics', 'seo-audit'])],
            'crawlLimit' => ['sometimes', 'integer', 'min:3', 'max:20'],
            'platformType' => ['sometimes', 'string', Rule::in(['auto', 'generic', 'media-platform', 'content-site', 'storefront'])],
            'interval' => ['required', 'string', Rule::in(['1', '3', '7', 'month'])],
            'sendTime' => ['required', 'date_format:H:i'],
            'timezone' => ['required', 'timezone'],
            'sendToBot' => ['sometimes', 'boolean'],
        ];
    }

    public function scheduleData(): array
    {
        return [
            'name' => $this->validated('name'), 'targets' => $this->validated('targets'),
            'report_type' => $this->validated('reportType'),
            'crawl_limit' => (int) $this->validated('crawlLimit', 8), 'platform_type' => $this->validated('platformType', 'auto'),
            'interval' => $this->validated('interval'), 'send_time' => $this->validated('sendTime'),
            'timezone' => $this->validated('timezone'), 'send_to_bot' => (bool) $this->validated('sendToBot', false),
        ];
    }
}
