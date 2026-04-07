<?php

namespace App\Modules\Telegram\DTO\Response\Info;

class ChatDTO
{
    public string $_;
    public int $flags;
    public bool $creator;
    public bool $left;
    public bool $broadcast;
    public bool $verified;
    public bool $megagroup;
    public bool $restricted;
    public bool $signatures;
    public bool $min;
    public bool $scam;
    public bool $has_link;
    public bool $has_geo;
    public bool $slowmode_enabled;
    public bool $forum;
    public bool $gigagroup;
    public int $id;
    public string $title;
    public ?string $username;
    public ?PhotoDTO $photo;
    public int $date;
    public ?string $about = null;
    public ?int $participants_count = null;
    public array $usernames = [];
    public array $raw = [];

    public function __construct(array $data)
    {
        $this->_ = $data['_'] ?? '';
        $this->flags = $data['flags'] ?? 0;
        $this->creator = $data['creator'] ?? false;
        $this->left = $data['left'] ?? false;
        $this->broadcast = $data['broadcast'] ?? false;
        $this->verified = $data['verified'] ?? false;
        $this->megagroup = $data['megagroup'] ?? false;
        $this->restricted = $data['restricted'] ?? false;
        $this->signatures = $data['signatures'] ?? false;
        $this->min = $data['min'] ?? false;
        $this->scam = $data['scam'] ?? false;
        $this->has_link = $data['has_link'] ?? false;
        $this->has_geo = $data['has_geo'] ?? false;
        $this->slowmode_enabled = $data['slowmode_enabled'] ?? false;
        $this->forum = $data['forum'] ?? false;
        $this->gigagroup = $data['gigagroup'] ?? false;
        $this->id = $data['id'] ?? 0;
        $this->title = $data['title'] ?? '';
        $this->username = $data['username'] ?? null;
        $this->photo = is_array($data['photo'] ?? null) ? new PhotoDTO($data['photo']) : null;
        $this->date = $data['date'] ?? 0;
        $this->about = $data['about'] ?? null;
        $this->participants_count = isset($data['participants_count']) ? (int) $data['participants_count'] : null;
        $this->usernames = is_array($data['usernames'] ?? null) ? $data['usernames'] : [];
        $this->raw = $data;
    }
}
