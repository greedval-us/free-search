<?php

namespace App\Modules\Telegram\DTO\Response\Participants;

class AdminRightsDTO
{
    public string $_;

    public bool $change_info;
    public bool $post_messages;
    public bool $edit_messages;
    public bool $delete_messages;
    public bool $ban_users;
    public bool $invite_users;
    public bool $pin_messages;
    public bool $add_admins;
    public bool $anonymous;
    public bool $manage_call;
    public bool $other;
    public bool $manage_topics;
    public bool $post_stories;
    public bool $edit_stories;
    public bool $delete_stories;
    public bool $manage_direct_messages;

    public function __construct(array $data)
    {
        $this->_ = $data['_'] ?? '';

        $this->change_info = $data['change_info'] ?? false;
        $this->post_messages = $data['post_messages'] ?? false;
        $this->edit_messages = $data['edit_messages'] ?? false;
        $this->delete_messages = $data['delete_messages'] ?? false;
        $this->ban_users = $data['ban_users'] ?? false;
        $this->invite_users = $data['invite_users'] ?? false;
        $this->pin_messages = $data['pin_messages'] ?? false;
        $this->add_admins = $data['add_admins'] ?? false;
        $this->anonymous = $data['anonymous'] ?? false;
        $this->manage_call = $data['manage_call'] ?? false;
        $this->other = $data['other'] ?? false;
        $this->manage_topics = $data['manage_topics'] ?? false;
        $this->post_stories = $data['post_stories'] ?? false;
        $this->edit_stories = $data['edit_stories'] ?? false;
        $this->delete_stories = $data['delete_stories'] ?? false;
        $this->manage_direct_messages = $data['manage_direct_messages'] ?? false;
    }
}
