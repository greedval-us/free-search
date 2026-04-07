<?php

namespace App\Services\Telegram\DTO\Response\Messages;

class ReactionDTO
{
    public ?string $emoticon = null;
    public ?int $count = null;

    public array $raw = [];

    public function __construct(array $data)
    {
        $this->raw = $data;

        $reaction = $data['reaction'] ?? null;
        if (is_array($reaction)) {
            $this->emoticon = $reaction['emoticon'] ?? null;
        } elseif (is_string($reaction)) {
            $this->emoticon = $reaction;
        }

        $this->count = $data['count'] ?? null;
    }
}
