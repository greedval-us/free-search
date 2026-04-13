<?php

namespace App\Modules\Telegram\DTO\Request;

class TelegramParserStartDTO
{
    /**
     * @param array{dateFrom: string|null, dateTo: string|null, minTimestamp: int|null, maxTimestamp: int|null} $range
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $chatUsername,
        public readonly string $period,
        public readonly ?string $keyword,
        public readonly array $range,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toContext(): array
    {
        return [
            'chatUsername' => $this->chatUsername,
            'period' => $this->period,
            'keyword' => $this->keyword,
            'range' => $this->range,
        ];
    }
}

