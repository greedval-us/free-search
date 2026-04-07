<?php

namespace App\Services\Telegram\DTO\Response\Messages;

class MediaDTO
{
    public string $_;
    public ?PhotoDTO $photo = null;
    public ?string $media_type_name = null;

    public array $raw = [];

    public function __construct(array $data)
    {
        $this->_ = $data['_'] ?? '';
        $this->media_type_name = is_string($data['_'] ?? null) ? $data['_'] : null;

        if (!empty($data['photo']) && is_array($data['photo'])) {
            $this->photo = new PhotoDTO($data['photo']);
        }

        $this->raw = $data;
    }
}
