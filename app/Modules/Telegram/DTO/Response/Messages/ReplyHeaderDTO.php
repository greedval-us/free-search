<?php

namespace App\Services\Telegram\DTO\Response\Messages;

class ReplyHeaderDTO
{
    public string $_;
    public ?int $reply_to_msg_id = null;
    public bool $forum_topic = false;
    public bool $quote = false;

    public array $raw = [];

    public function __construct(array $data)
    {
        $this->_ = $data['_'] ?? '';
        $this->reply_to_msg_id = $data['reply_to_msg_id'] ?? null;
        $this->forum_topic = $data['forum_topic'] ?? false;
        $this->quote = $data['quote'] ?? false;
        $this->raw = $data;
    }
}
