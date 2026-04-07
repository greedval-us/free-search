<?php

namespace App\Modules\Telegram\DTO\Response\Messages;

class ChatPhotoDTO
{
    public string $_;
    public bool $has_video = false;
    public ?int $photo_id = null;
    public ?string $stripped_thumb = null;
    public ?int $dc_id = null;

    public array $raw = [];

    public function __construct(array $data)
    {
        $this->_ = $data['_'] ?? '';
        $this->has_video = $data['has_video'] ?? false;
        $this->photo_id = $data['photo_id'] ?? null;

        if (isset($data['stripped_thumb']['bytes'])) {
            $this->stripped_thumb = $data['stripped_thumb']['bytes'];
        } else {
            $this->stripped_thumb = $data['stripped_thumb'] ?? null;
        }

        $this->dc_id = $data['dc_id'] ?? null;
        $this->raw = $data;
    }
}
