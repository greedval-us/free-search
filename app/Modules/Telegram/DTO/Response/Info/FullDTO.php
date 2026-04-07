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
    public ?int $participants_count;
    public ?int $online_count;
    public ?int $read_inbox_max_id;
    public ?int $read_outbox_max_id;
    public ?int $unread_count;
    public ?ChatPhotoDTO $chat_photo;
    public ?int $pinned_msg_id;
    public ?int $linked_chat_id;
    public ?int $pts;
    public ?AvailableReactionsDTO $available_reactions;
    public ?int $reactions_limit;
    public ?int $stargifts_count;
    public array $bot_info;
    public ?int $slowmode_seconds = null;
    public ?int $ttl_period = null;
    public ?int $migrated_from_chat_id = null;
    public ?int $migrated_from_max_id = null;
    public mixed $exported_invite = null;
    public mixed $notify_settings = null;
    public mixed $stats_dc = null;
    public array $raw = [];

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
        $this->participants_count = isset($data['participants_count']) ? (int) $data['participants_count'] : null;
        $this->online_count = isset($data['online_count']) ? (int) $data['online_count'] : null;
        $this->read_inbox_max_id = isset($data['read_inbox_max_id']) ? (int) $data['read_inbox_max_id'] : null;
        $this->read_outbox_max_id = isset($data['read_outbox_max_id']) ? (int) $data['read_outbox_max_id'] : null;
        $this->unread_count = isset($data['unread_count']) ? (int) $data['unread_count'] : null;
        $this->chat_photo = is_array($data['chat_photo'] ?? null) ? new ChatPhotoDTO($data['chat_photo']) : null;
        $this->pinned_msg_id = isset($data['pinned_msg_id']) ? (int) $data['pinned_msg_id'] : null;
        $this->linked_chat_id = isset($data['linked_chat_id']) ? (int) $data['linked_chat_id'] : null;
        $this->pts = isset($data['pts']) ? (int) $data['pts'] : null;
        $this->available_reactions = is_array($data['available_reactions'] ?? null)
            ? new AvailableReactionsDTO($data['available_reactions'])
            : null;
        $this->reactions_limit = isset($data['reactions_limit']) ? (int) $data['reactions_limit'] : null;
        $this->stargifts_count = isset($data['stargifts_count']) ? (int) $data['stargifts_count'] : null;
        $this->bot_info = $data['bot_info'] ?? [];
        $this->slowmode_seconds = isset($data['slowmode_seconds']) ? (int) $data['slowmode_seconds'] : null;
        $this->ttl_period = isset($data['ttl_period']) ? (int) $data['ttl_period'] : null;
        $this->migrated_from_chat_id = isset($data['migrated_from_chat_id']) ? (int) $data['migrated_from_chat_id'] : null;
        $this->migrated_from_max_id = isset($data['migrated_from_max_id']) ? (int) $data['migrated_from_max_id'] : null;
        $this->exported_invite = $data['exported_invite'] ?? null;
        $this->notify_settings = $data['notify_settings'] ?? null;
        $this->stats_dc = $data['stats_dc'] ?? null;
        $this->raw = $data;
    }
}
