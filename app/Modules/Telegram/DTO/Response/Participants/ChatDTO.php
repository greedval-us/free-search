<?php

namespace App\Modules\Telegram\DTO\Response\Participants;

class ChatDTO
{
    public string $_;
    public int $flags;
    public int $id;
    public string $title;
    public ?string $username = null;
    public bool $broadcast = false;
    public bool $megagroup = false;
    public bool $verified = false;
    public bool $restricted = false;
    public bool $scam = false;
    public bool $fake = false;
    public ?array $photo = null;
    public ?int $participants_count = null;
    public array $raw = [];

    public function __construct(array $data)
    {
        $this->_ = $data['_'] ?? '';
        $this->flags = (int) ($data['flags'] ?? 0);
        $this->id = $data['id'] ?? 0;
        $this->title = $data['title'] ?? '';
        $this->username = $data['username'] ?? null;
        $this->broadcast = (bool) ($data['broadcast'] ?? false);
        $this->megagroup = (bool) ($data['megagroup'] ?? false);
        $this->verified = (bool) ($data['verified'] ?? false);
        $this->restricted = (bool) ($data['restricted'] ?? false);
        $this->scam = (bool) ($data['scam'] ?? false);
        $this->fake = (bool) ($data['fake'] ?? false);
        $this->photo = is_array($data['photo'] ?? null) ? $data['photo'] : null;
        $this->participants_count = isset($data['participants_count']) ? (int) $data['participants_count'] : null;
        $this->raw = $data;
    }
}
