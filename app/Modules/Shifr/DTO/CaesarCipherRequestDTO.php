<?php

namespace App\Modules\Shifr\DTO;

class CaesarCipherRequestDTO
{
    public string $message;
    public int $shift;

    public function __construct(array $data)
    {
        $this->message = $data['message'] ?? '';
        $this->shift   = (int) ($data['shift'] ?? 0);
    }

    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'shift'   => $this->shift,
        ];
    }
}
