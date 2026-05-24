<?php

namespace App\Modules\Shifr\DTO\Classic;

class AtbashRequestDTO
{
    public string $message;

    public function __construct(array $data)
    {
        $this->message = $data['message'] ?? '';
    }

    public function toArray(): array
    {
        return [
            'message' => $this->message,
        ];
    }
}
