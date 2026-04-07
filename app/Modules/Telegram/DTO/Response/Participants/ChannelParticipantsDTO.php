<?php

namespace App\Services\Telegram\DTO\Response\Participants;

class ChannelParticipantsDTO
{
    public string $_;
    public int $count;

    /** @var ParticipantDTO[] */
    public array $participants = [];
    /** @var ChatDTO[] */
    public array $chats = [];
    /** @var UserDTO[] */
    public array $users = [];

    public function __construct(array $data)
    {
        $this->_ = $data['_'] ?? '';
        $this->count = $data['count'] ?? 0;

        foreach ($data['participants'] ?? [] as $p) {
            $this->participants[] = new ParticipantDTO((array)$p);
        }

        foreach ($data['chats'] ?? [] as $c) {
            $this->chats[] = new ChatDTO((array)$c);
        }

        foreach ($data['users'] ?? [] as $u) {
            $this->users[] = new UserDTO((array)$u);
        }
    }
}
