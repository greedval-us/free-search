<?php

namespace App\Modules\Telegram\DTO\Response\Messages;

class ChatDTO
{
    public string $_;
    public int $id;
    public string $title;
    public ?string $username = null;
    public ?ChatPhotoDTO $photo = null;

    public ?string $description = null;
    public ?string $type = null;
    public ?int $members_count = null;
    public ?MessageDTO $pinned_message = null;

    public bool $creator = false;
    public bool $left = false;
    public bool $broadcast = false;
    public bool $verified = false;
    public bool $megagroup = false;
    public bool $restricted = false;
    public bool $signatures = false;
    public bool $min = false;
    public bool $scam = false;
    public bool $has_link = false;
    public bool $has_geo = false;
    public bool $slowmode_enabled = false;

    public array $raw = [];

    public function __construct(array $data)
    {
        $this->_ = $data['_'] ?? '';
        $this->id = $data['id'] ?? 0;
        $this->title = $data['title'] ?? '';
        $this->username = $data['username'] ?? null;

        $this->photo = isset($data['photo']) ? new ChatPhotoDTO($data['photo']) : null;
        $this->description = $data['description'] ?? null;
        $this->type = $data['type'] ?? null;
        $this->members_count = $data['members_count'] ?? null;

        $this->pinned_message = isset($data['pinned_message'])
            ? new MessageDTO((array)$data['pinned_message'])
            : null;

        foreach ([
            'creator','left','broadcast','verified','megagroup','restricted',
            'signatures','min','scam','has_link','has_geo','slowmode_enabled'
        ] as $flag) {
            $this->$flag = $data[$flag] ?? false;
        }

        $this->raw = $data;
    }
}
