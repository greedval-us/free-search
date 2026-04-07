<?php

namespace App\Modules\Telegram\DTO\Response\Participants;

class ParticipantDTO
{
    public string $_;
    public int $flags = 0;
    public int $user_id;
    public int $date;
    public bool $self;
    public bool $can_edit = false;
    public ?int $inviter_id = null;
    public ?int $promoted_by = null;
    public ?int $kicked_by = null;
    public ?int $banned_rights_until_date = null;

    public ?AdminRightsDTO $admin_rights = null;
    public array $raw = [];

    public function __construct(array $data)
    {
        $this->_ = $data['_'] ?? '';
        $this->flags = (int) ($data['flags'] ?? 0);
        $this->user_id = $data['user_id'] ?? 0;
        $this->date = $data['date'] ?? 0;
        $this->self = $data['self'] ?? false;
        $this->can_edit = $data['can_edit'] ?? false;
        $this->inviter_id = isset($data['inviter_id']) ? (int) $data['inviter_id'] : null;
        $this->promoted_by = isset($data['promoted_by']) ? (int) $data['promoted_by'] : null;
        $this->kicked_by = isset($data['kicked_by']) ? (int) $data['kicked_by'] : null;
        $this->banned_rights_until_date = isset($data['banned_rights']['until_date'])
            ? (int) $data['banned_rights']['until_date']
            : null;

        $this->admin_rights = isset($data['admin_rights'])
            ? new AdminRightsDTO((array)$data['admin_rights'])
            : null;
        $this->raw = $data;
    }
}
