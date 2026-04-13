<?php

namespace App\Modules\Telegram\DTO\Result;

class SearchMessagesResultDTO
{
    /**
     * @param array<int, mixed> $items
     * @param array{limit: int, offsetId: int, nextOffsetId: int|null, hasMore: bool, total: int} $pagination
     */
    public function __construct(
        public readonly bool $ok,
        public readonly array $items,
        public readonly array $pagination,
        public readonly ?string $message = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'ok' => $this->ok,
            'items' => $this->items,
            'pagination' => $this->pagination,
        ];

        if ($this->message !== null) {
            $payload['message'] = $this->message;
        }

        return $payload;
    }
}
