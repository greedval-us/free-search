<?php

namespace App\Modules\Fio\Domain\DTO;

final class FioMatchDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $snippet,
        public readonly string $url,
        public readonly ?string $domain,
        public readonly string $source,
        public readonly float $sourceReliability,
        public readonly string $region,
        public readonly ?int $age,
        public readonly string $ageBucket,
        public readonly ?string $qualifier,
        public readonly bool $qualifierMatched,
        public readonly int $confidence,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'snippet' => $this->snippet,
            'url' => $this->url,
            'domain' => $this->domain,
            'source' => $this->source,
            'sourceReliability' => $this->sourceReliability,
            'region' => $this->region,
            'age' => $this->age,
            'ageBucket' => $this->ageBucket,
            'qualifier' => $this->qualifier,
            'qualifierMatched' => $this->qualifierMatched,
            'confidence' => $this->confidence,
        ];
    }
}
