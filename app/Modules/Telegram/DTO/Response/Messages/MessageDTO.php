<?php

namespace App\Modules\Telegram\DTO\Response\Messages;

class MessageDTO
{
    public string $_;
    public bool $out;
    public bool $mentioned;
    public bool $media_unread;
    public bool $silent;
    public bool $post;
    public bool $from_scheduled;
    public bool $legacy;
    public bool $edit_hide;
    public bool $pinned;
    public bool $noforwards;
    public bool $invert_media;
    public bool $offline;
    public bool $video_processing_pending;
    public bool $paid_suggested_post_stars;
    public bool $paid_suggested_post_ton;

    public int $id;
    public int $from_id;
    public int $peer_id;
    public int $date;
    public string $message;

    public ?MessageRepliesDTO $replies = null;
    public ?ReplyHeaderDTO $reply_to = null;
    /** @var MessageEntityDTO[]|null */
    public ?array $entities = null;
    public MediaDTO|array|string|null $media = null;
    /** @var ReactionDTO[]|null */
    public ?array $reactions = null;

    public ?int $forward_from = null;
    public ?int $views = null;
    public ?int $forwards = null;

    public ?int $via_bot_id = null;
    public ?string $author_signature = null;
    public ?string $post_author = null;

    public array $raw = [];

    public function __construct(array $data)
    {
        $this->_ = $data['_'] ?? '';
        $this->out = $data['out'] ?? false;
        $this->mentioned = $data['mentioned'] ?? false;
        $this->media_unread = $data['media_unread'] ?? false;
        $this->silent = $data['silent'] ?? false;
        $this->post = $data['post'] ?? false;
        $this->from_scheduled = $data['from_scheduled'] ?? false;
        $this->legacy = $data['legacy'] ?? false;
        $this->edit_hide = $data['edit_hide'] ?? false;
        $this->pinned = $data['pinned'] ?? false;
        $this->noforwards = $data['noforwards'] ?? false;
        $this->invert_media = $data['invert_media'] ?? false;
        $this->offline = $data['offline'] ?? false;
        $this->video_processing_pending = $data['video_processing_pending'] ?? false;
        $this->paid_suggested_post_stars = $data['paid_suggested_post_stars'] ?? false;
        $this->paid_suggested_post_ton = $data['paid_suggested_post_ton'] ?? false;

        $this->id = $data['id'] ?? 0;
        $this->from_id = $data['from_id'] ?? 0;
        $this->peer_id = $data['peer_id'] ?? 0;
        $this->date = $data['date'] ?? 0;
        $this->message = $data['message'] ?? '';

        $this->replies = isset($data['replies']) ? new MessageRepliesDTO($data['replies']) : null;
        $this->reply_to = isset($data['reply_to']) ? new ReplyHeaderDTO($data['reply_to']) : null;

        if (!empty($data['entities'])) {
            foreach ($data['entities'] as $e) {
                $this->entities[] = new MessageEntityDTO((array)$e);
            }
        }

        $this->media = isset($data['media']) && is_array($data['media'])
            ? new MediaDTO($data['media'])
            : $data['media'] ?? null;

        if (!empty($data['reactions']['results'])) {
            foreach ($data['reactions']['results'] as $r) {
                $this->reactions[] = new ReactionDTO((array)$r);
            }
        }

        $this->forward_from = $data['forward_from'] ?? null;
        $this->views = $data['views'] ?? null;
        $this->forwards = $data['forwards'] ?? null;

        $this->via_bot_id = $data['via_bot_id'] ?? null;
        $this->author_signature = $data['author_signature'] ?? null;
        $this->post_author = $data['post_author'] ?? null;

        $this->raw = $data;
    }
}
