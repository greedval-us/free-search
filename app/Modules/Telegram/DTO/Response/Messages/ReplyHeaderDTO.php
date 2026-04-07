<?php

namespace App\Modules\Telegram\DTO\Response\Messages;

class ReplyHeaderDTO
{
    public string $_;
    public ?int $reply_to_msg_id = null;
    public ?int $reply_to_top_id = null;
    public mixed $reply_to_peer_id = null;
    public bool $forum_topic = false;
    public bool $quote = false;
    public ?string $quote_text = null;
    public array $quote_entities = [];

    public array $raw = [];

    public function __construct(array $data)
    {
        $this->_ = $data['_'] ?? '';
        $this->reply_to_msg_id = $data['reply_to_msg_id'] ?? null;
        $this->reply_to_top_id = $data['reply_to_top_id'] ?? null;
        $this->reply_to_peer_id = $data['reply_to_peer_id'] ?? null;
        $this->forum_topic = $data['forum_topic'] ?? false;
        $this->quote = $data['quote'] ?? false;
        $this->quote_text = $data['quote_text'] ?? null;
        $this->quote_entities = is_array($data['quote_entities'] ?? null)
            ? $data['quote_entities']
            : [];
        $this->raw = $data;
    }
}
