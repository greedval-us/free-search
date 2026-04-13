<?php

namespace App\Modules\Gdelt\DTO\Request;

class GdeltSearchQueryDTO
{
    public function __construct(
        public readonly string $query,
        public readonly int $maxRecords = 50,
        public readonly string $sort = 'datedesc',
        public readonly ?string $startDateTime = null,
        public readonly ?string $endDateTime = null,
        public readonly ?string $sourceCountry = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiParams(): array
    {
        $params = [
            'query' => $this->query,
            'mode' => 'ArtList',
            'format' => 'json',
            'maxrecords' => $this->maxRecords,
            'sort' => $this->sort,
        ];

        if ($this->startDateTime !== null && $this->endDateTime !== null) {
            $params['startdatetime'] = $this->startDateTime;
            $params['enddatetime'] = $this->endDateTime;
        }

        if ($this->sourceCountry !== null && $this->sourceCountry !== '') {
            $params['query'] .= ' sourcecountry:' . strtolower($this->sourceCountry);
        }

        return $params;
    }
}
