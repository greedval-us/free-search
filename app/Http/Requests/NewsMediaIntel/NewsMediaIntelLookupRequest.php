<?php

namespace App\Http\Requests\NewsMediaIntel;

use App\Http\Requests\AbstractLocalizedRequest;
use App\Http\Requests\NewsMediaIntel\Concerns\HasNewsSearchFilters;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsMediaIntelLookupDTO;

class NewsMediaIntelLookupRequest extends AbstractLocalizedRequest
{
    use HasNewsSearchFilters;

    protected function prepareForValidation(): void
    {
        $this->prepareSearchFilters();
    }

    public function rules(): array
    {
        return $this->searchFilterRules();
    }

    public function searchQuery(): string
    {
        return trim((string) $this->validated('query'));
    }

    public function toLookupDTO(): NewsMediaIntelLookupDTO
    {
        return new NewsMediaIntelLookupDTO($this->searchQuery(), $this->searchOptions());
    }
}
