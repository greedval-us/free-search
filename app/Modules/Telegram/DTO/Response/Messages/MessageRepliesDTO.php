<?php

namespace App\Modules\Telegram\DTO\Response\Messages;

class MessageRepliesDTO
{
    public string $_;
    public bool $comments;
    public int $replies;
    public int $replies_pts;
    public ?int $channel_id = null;
    public ?int $max_id = null;
    public ?int $read_max_id = null;
    public array $recent_repliers = [];

    public array $raw = [];

    public function __construct(array $data)
    {
        $this->_ = $data['_'] ?? '';
        $this->comments = $data['comments'] ?? false;
        $this->replies = $data['replies'] ?? 0;
        $this->replies_pts = $data['replies_pts'] ?? 0;
        $this->channel_id = isset($data['channel_id']) ? (int) $data['channel_id'] : null;
        $this->max_id = isset($data['max_id']) ? (int) $data['max_id'] : null;
        $this->read_max_id = isset($data['read_max_id']) ? (int) $data['read_max_id'] : null;
        $this->recent_repliers = is_array($data['recent_repliers'] ?? null)
            ? $data['recent_repliers']
            : [];
        $this->raw = $data;
    }
}
