<?php

namespace App\Modules\Telegram\DTO\Response\Messages;

class UserDTO
{
    public string $_;
    public int $id;
    public string $first_name;
    public ?string $last_name = null;
    public ?string $username = null;

    public ?object $photo = null;
    public ?array $status = null;

    public bool $bot = false;
    public bool $verified = false;
    public bool $restricted = false;
    public bool $scam = false;
    public bool $fake = false;
    public bool $premium = false;

    public ?string $phone = null;
    public ?string $language_code = null;

    public array $raw = [];

    public function __construct(array $data)
    {
        $this->_ = $data['_'] ?? '';
        $this->id = $data['id'] ?? 0;
        $this->first_name = $data['first_name'] ?? '';
        $this->last_name = $data['last_name'] ?? null;
        $this->username = $data['username'] ?? null;

        $this->photo = isset($data['photo']) ? (object)$data['photo'] : null;
        $this->status = $data['status'] ?? null;

        $this->bot = $data['bot'] ?? false;
        $this->verified = $data['verified'] ?? false;
        $this->restricted = $data['restricted'] ?? false;
        $this->scam = $data['scam'] ?? false;
        $this->fake = $data['fake'] ?? false;
        $this->premium = $data['premium'] ?? false;

        $this->phone = $data['phone'] ?? null;
        $this->language_code = $data['language_code'] ?? null;

        $this->raw = $data;
    }
}
