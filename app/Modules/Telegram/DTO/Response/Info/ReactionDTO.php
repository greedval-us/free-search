<?php

namespace App\Modules\Telegram\DTO\Response\Info;

class ReactionDTO
{
    public string $emoticon;

    public function __construct(array $data)
    {
        $this->emoticon = $data['emoticon'] ?? '';
    }
}
