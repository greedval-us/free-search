<?php

namespace App\Http\Requests\NewsMediaIntel;

use App\Http\Requests\AbstractLocalizedRequest;
use App\Http\Requests\NewsMediaIntel\Concerns\HasNewsMarketingFilters;
use App\Http\Requests\NewsMediaIntel\Concerns\HasNewsSearchFilters;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsMarketingLookupDTO;

final class NewsMarketingAnalyticsRequest extends AbstractLocalizedRequest
{
    use HasNewsMarketingFilters;
    use HasNewsSearchFilters;

    protected function prepareForValidation(): void
    {
        $this->prepareSearchFilters();
        $this->prepareMarketingFilters();
    }

    public function rules(): array
    {
        return [...$this->searchFilterRules(), ...$this->marketingFilterRules()];
    }

    public function toLookupDTO(): NewsMarketingLookupDTO
    {
        return new NewsMarketingLookupDTO(
            query: $this->validated('query'), options: $this->searchOptions(),
            brand: $this->validated('brand') ?? '', competitors: $this->validated('competitors', []),
            domain: $this->marketingDomain(),
        );
    }
}
