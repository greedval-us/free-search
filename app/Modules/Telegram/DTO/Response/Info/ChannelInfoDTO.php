<?php

namespace App\Modules\Telegram\DTO\Response\Info;

class ChannelInfoDTO
{
    public int $channel_id;
    public int $bot_api_id;
    public string $type;
    public ?ChatDTO $chat;
    public ?FullDTO $full;
    public int $inserted;
    public int $id;
    public array $raw = [];

    public function __construct(array $data)
    {
        $this->channel_id = $data['channel_id'] ?? 0;
        $this->bot_api_id = $data['bot_api_id'] ?? 0;
        $this->type = $data['type'] ?? '';
        $chatData = $data['Chat'] ?? $data['chat'] ?? null;
        $this->chat = is_array($chatData) ? new ChatDTO($chatData) : null;

        $fullData = $data['full'] ?? null;
        $this->full = is_array($fullData) ? new FullDTO($fullData) : null;
        $this->inserted = $data['inserted'] ?? 0;
        $this->id = $data['id'] ?? 0;
        $this->raw = $data;
    }
}
