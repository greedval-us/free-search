<?php

namespace App\Modules\Telegram\DTO\Response\Participants;

class ChatDTO
{
    public string $_;
    public int $id;
    public string $title;

    public function __construct(array $data)
    {
        $this->_ = $data['_'] ?? '';
        $this->id = $data['id'] ?? 0;
        $this->title = $data['title'] ?? '';
    }
}
