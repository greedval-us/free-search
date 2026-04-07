<?php

namespace App\Modules\Telegram\DTO\Response\Messages;

class MessageRepliesDTO
{
    public string $_;
    public bool $comments;
    public int $replies;
    public int $replies_pts;

    public array $raw = [];

    public function __construct(array $data)
    {
        $this->_ = $data['_'] ?? '';
        $this->comments = $data['comments'] ?? false;
        $this->replies = $data['replies'] ?? 0;
        $this->replies_pts = $data['replies_pts'] ?? 0;
        $this->raw = $data;
    }
}
