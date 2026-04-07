<?php

namespace App\Services\Telegram\DTO\Response\Participants;

class ParticipantDTO
{
    public string $_;
    public int $user_id;
    public int $date;
    public bool $self;
    public bool $can_edit = false;

    public ?AdminRightsDTO $admin_rights = null;

    public function __construct(array $data)
    {
        $this->_ = $data['_'] ?? '';
        $this->user_id = $data['user_id'] ?? 0;
        $this->date = $data['date'] ?? 0;
        $this->self = $data['self'] ?? false;
        $this->can_edit = $data['can_edit'] ?? false;

        $this->admin_rights = isset($data['admin_rights'])
            ? new AdminRightsDTO((array)$data['admin_rights'])
            : null;
    }
}
