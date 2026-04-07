<?php

namespace App\Services\Telegram\DTO\Response\Messages;

class ChannelMessagesDTO
{
    public string $_;
    public bool $inexact;
    public int $pts;
    public int $count;
    public ?string $next_offset = null;
    public ?int $unread_count = null;

    /** @var MessageDTO[] */
    public array $messages = [];
    /** @var ChatDTO[] */
    public array $chats = [];
    /** @var UserDTO[] */
    public array $users = [];
    public array $topics = [];

    public array $raw = [];

    public function __construct(array $data)
    {
        $this->_ = $data['_'] ?? '';
        $this->inexact = $data['inexact'] ?? false;
        $this->pts = $data['pts'] ?? 0;
        $this->count = $data['count'] ?? 0;
        $this->next_offset = $data['next_offset'] ?? null;
        $this->unread_count = $data['unread_count'] ?? null;

        foreach ($data['messages'] ?? [] as $msg) {
            $this->messages[] = new MessageDTO((array)$msg);
        }
        foreach ($data['chats'] ?? [] as $chat) {
            $this->chats[] = new ChatDTO((array)$chat);
        }
        foreach ($data['users'] ?? [] as $user) {
            $this->users[] = new UserDTO((array)$user);
        }

        $this->topics = $data['topics'] ?? [];
        $this->raw = $data;
    }
}
