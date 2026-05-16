<?php

namespace App\Modules\NewsMediaIntel\Domain\DTO;

final class NewsTopicDTO
{
    public function __construct(
        public readonly string $topic,
        public readonly int $count,
    ) {
    }

    /**
     * @return array{topic: string, count: int}
     */
    public function toArray(): array
    {
        return [
            'topic' => $this->topic,
            'count' => $this->count,
        ];
    }
}

