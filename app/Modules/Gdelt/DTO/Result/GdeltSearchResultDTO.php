<?php

namespace App\Modules\Gdelt\DTO\Result;

class GdeltSearchResultDTO
{
    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function __construct(
        public readonly bool $ok,
        public readonly array $items,
        public readonly int $total,
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
            'total' => $this->total,
        ];

        if ($this->message !== null) {
            $payload['message'] = $this->message;
        }

        return $payload;
    }
}
