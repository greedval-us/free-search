<?php

namespace App\Modules\Telegram\DTO\Response\Messages;

class MediaDTO
{
    public string $_;
    public ?int $ttl_seconds = null;
    public bool $spoiler = false;
    public ?PhotoDTO $photo = null;
    public object|array|null $document = null;
    public object|array|null $webpage = null;
    public object|array|null $geo = null;
    public object|array|null $contact = null;
    public object|array|null $game = null;
    public object|array|null $poll = null;
    public object|array|null $venue = null;
    public object|array|null $invoice = null;
    public ?string $media_type_name = null;

    public array $raw = [];

    public function __construct(array $data)
    {
        $this->_ = $data['_'] ?? '';
        $this->media_type_name = is_string($data['_'] ?? null) ? $data['_'] : null;
        $this->ttl_seconds = isset($data['ttl_seconds']) ? (int) $data['ttl_seconds'] : null;
        $this->spoiler = (bool) ($data['spoiler'] ?? false);

        if (!empty($data['photo']) && is_array($data['photo'])) {
            $this->photo = new PhotoDTO($data['photo']);
        }

        foreach (['document', 'webpage', 'geo', 'contact', 'game', 'poll', 'venue', 'invoice'] as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];
            if (is_array($value)) {
                $this->{$field} = $value;
            } elseif (is_object($value)) {
                $this->{$field} = $value;
            }
        }

        $this->raw = $data;
    }
}
