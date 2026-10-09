<?php

namespace App\Http\Requests\NewsMediaIntel;

use App\Http\Requests\AbstractLocalizedRequest;
use App\Http\Requests\NewsMediaIntel\Concerns\HasNewsMarketingFilters;
use App\Http\Requests\NewsMediaIntel\Concerns\HasNewsSearchFilters;
use App\Models\NewsMediaReportSchedule;
use App\Modules\NewsMediaIntel\Application\Reports\ReportConfig;
use Illuminate\Validation\Rule;

final class NewsMediaReportScheduleRequest extends AbstractLocalizedRequest
{
    use HasNewsMarketingFilters;
    use HasNewsSearchFilters;

    public function authorize(): bool
    {
        return $this->user() !== null && ! $this->user()->isBlocked() && $this->user()->hasVerifiedEmail();
    }

    protected function prepareForValidation(): void
    {
        $this->prepareMarketingFilters();
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
        if (is_array($this->input('queries'))) {
            $this->merge(['queries' => array_map(static fn (mixed $value): mixed => is_string($value) ? trim($value) : $value, $this->input('queries'))]);
        }
    }

    public function rules(): array
    {
        if ($this->routeIs('news-media-intel.reports.change')) {
            return ['action' => ['required', Rule::in(['pause', 'resume'])], 'locale' => $this->localeRule()];
        }
        $filters = $this->searchFilterRules();
        $query = $filters['query'];
        unset($filters['query'], $filters['categories'], $filters['categories.*']);

        return [...$filters, ...$this->marketingFilterRules(),
            'name' => ['required', 'string', 'max:100', 'not_regex:/[\p{Cc}]/u'],
            'queries' => ['required', 'array', 'list', 'min:1', 'max:'.app(ReportConfig::class)->maxQueries()],
            'queries.*' => [...$query, 'distinct:ignore_case'],
            'interval' => ['required', 'string', Rule::in(NewsMediaReportSchedule::INTERVALS)],
            'sendTime' => ['required', 'date_format:H:i'],
            'timezone' => ['required', 'timezone'],
            'sendToBot' => ['sometimes', 'boolean'],
        ];
    }

    public function scheduleData(): array
    {
        return [
            'name' => $this->validated('name'), 'queries' => $this->validated('queries'),
            'brand' => $this->validated('brand') ?? '', 'competitors' => $this->validated('competitors', []),
            'domain' => $this->marketingDomain(),
            'search_options' => [...$this->searchOptions()->toArray(), 'categories' => ['general', 'news']],
            'interval' => $this->validated('interval'), 'send_time' => $this->validated('sendTime'),
            'timezone' => $this->validated('timezone'), 'send_to_bot' => (bool) $this->validated('sendToBot', false),
        ];
    }
}
