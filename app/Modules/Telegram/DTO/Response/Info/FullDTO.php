<?php

namespace App\Modules\Telegram\DTO\Response\Info;

class FullDTO
{
    public string $_;
    public int $flags;
    public bool $can_view_participants;
    public bool $can_set_username;
    public bool $can_set_stickers;
    public bool $hidden_prehistory;
    public bool $can_set_location;
    public bool $has_scheduled;
    public bool $can_view_stats;
    public bool $blocked;
    public int $flags2;
    public bool $can_delete_channel;
    public bool $antispam;
    public bool $participants_hidden;
    public bool $translations_disabled;
    public bool $stories_pinned_available;
    public bool $view_forum_as_messages;
    public bool $restricted_sponsored;
    public bool $can_view_revenue;
    public bool $paid_media_allowed;
    public bool $can_view_stars_revenue;
    public bool $paid_reactions_available;
    public bool $stargifts_available;
    public bool $paid_messages_available;
    public int $id;
    public string $about;
    public int $participants_count;
    public int $online_count;
    public int $read_inbox_max_id;
    public int $read_outbox_max_id;
    public int $unread_count;
    public ChatPhotoDTO $chat_photo;
    public int $pinned_msg_id;
    public int $linked_chat_id;
    public int $pts;
    public AvailableReactionsDTO $available_reactions;
    public int $reactions_limit;
    public int $stargifts_count;
    public array $bot_info;

    public function __construct(array $data)
    {
        $this->_ = $data['_'] ?? '';
        $this->flags = $data['flags'] ?? 0;
        $this->can_view_participants = $data['can_view_participants'] ?? false;
        $this->can_set_username = $data['can_set_username'] ?? false;
        $this->can_set_stickers = $data['can_set_stickers'] ?? false;
        $this->hidden_prehistory = $data['hidden_prehistory'] ?? false;
        $this->can_set_location = $data['can_set_location'] ?? false;
        $this->has_scheduled = $data['has_scheduled'] ?? false;
        $this->can_view_stats = $data['can_view_stats'] ?? false;
        $this->blocked = $data['blocked'] ?? false;
        $this->flags2 = $data['flags2'] ?? 0;
        $this->can_delete_channel = $data['can_delete_channel'] ?? false;
        $this->antispam = $data['antispam'] ?? false;
        $this->participants_hidden = $data['participants_hidden'] ?? false;
        $this->translations_disabled = $data['translations_disabled'] ?? false;
        $this->stories_pinned_available = $data['stories_pinned_available'] ?? false;
        $this->view_forum_as_messages = $data['view_forum_as_messages'] ?? false;
        $this->restricted_sponsored = $data['restricted_sponsored'] ?? false;
        $this->can_view_revenue = $data['can_view_revenue'] ?? false;
        $this->paid_media_allowed = $data['paid_media_allowed'] ?? false;
        $this->can_view_stars_revenue = $data['can_view_stars_revenue'] ?? false;
        $this->paid_reactions_available = $data['paid_reactions_available'] ?? false;
        $this->stargifts_available = $data['stargifts_available'] ?? false;
        $this->paid_messages_available = $data['paid_messages_available'] ?? false;
        $this->id = $data['id'] ?? 0;
        $this->about = $data['about'] ?? '';
        $this->participants_count = $data['participants_count'] ?? 0;
        $this->online_count = $data['online_count'] ?? 0;
        $this->read_inbox_max_id = $data['read_inbox_max_id'] ?? 0;
        $this->read_outbox_max_id = $data['read_outbox_max_id'] ?? 0;
        $this->unread_count = $data['unread_count'] ?? 0;
        $this->chat_photo = new ChatPhotoDTO($data['chat_photo'] ?? null);
        $this->pinned_msg_id = $data['pinned_msg_id'] ?? 0;
        $this->linked_chat_id = $data['linked_chat_id'] ?? 0;
        $this->pts = $data['pts'] ?? 0;
        $this->available_reactions = new AvailableReactionsDTO($data['available_reactions'] ?? []);
        $this->reactions_limit = $data['reactions_limit'] ?? 0;
        $this->stargifts_count = $data['stargifts_count'] ?? 0;
        $this->bot_info = $data['bot_info'] ?? [];
    }
}
