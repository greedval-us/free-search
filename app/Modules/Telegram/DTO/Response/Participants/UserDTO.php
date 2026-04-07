<?php

namespace App\Services\Telegram\DTO\Response\Participants;

class UserDTO
{
    public string $_;

    public bool $self;
    public bool $contact;
    public bool $mutual_contact;
    public bool $deleted;
    public bool $bot;
    public bool $bot_chat_history;
    public bool $bot_nochats;
    public bool $verified;
    public bool $restricted;
    public bool $min;
    public bool $bot_inline_geo;
    public bool $support;
    public bool $scam;
    public bool $apply_min_photo;
    public bool $fake;
    public bool $bot_attach_menu;
    public bool $premium;
    public bool $attach_menu_enabled;
    public bool $bot_can_edit;
    public bool $close_friend;
    public bool $stories_hidden;
    public bool $stories_unavailable;
    public bool $contact_require_premium;
    public bool $bot_business;
    public bool $bot_has_main_app;

    public int $id;
    public ?string $first_name;
    public ?string $username;

    public ?object $photo = null;
    public ?int $bot_info_version = null;
    public ?int $bot_active_users = null;

    public function __construct(array $data)
    {
        $this->_ = $data['_'] ?? '';

        foreach ([
            'self','contact','mutual_contact','deleted','bot','bot_chat_history',
            'bot_nochats','verified','restricted','min','bot_inline_geo','support',
            'scam','apply_min_photo','fake','bot_attach_menu','premium',
            'attach_menu_enabled','bot_can_edit','close_friend','stories_hidden',
            'stories_unavailable','contact_require_premium','bot_business',
            'bot_has_main_app'
        ] as $flag) {
            $this->$flag = $data[$flag] ?? false;
        }

        $this->id = $data['id'] ?? 0;
        $this->first_name = $data['first_name'] ?? null;
        $this->username = $data['username'] ?? null;

        $this->photo = isset($data['photo']) ? (object)$data['photo'] : null;
        $this->bot_info_version = $data['bot_info_version'] ?? null;
        $this->bot_active_users = $data['bot_active_users'] ?? null;
    }
}
