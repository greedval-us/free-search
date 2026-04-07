<?php

namespace App\Modules\Telegram\DTO\Response\Messages;

class PhotoSizeDTO
{
    public string $_;
    public ?string $type = null;
    public ?int $w = null;
    public ?int $h = null;
    public ?int $size = null;
    public ?array $sizes = null;
    public ?string $bytes = null;

    public array $raw = [];

    public function __construct(array $data)
    {
        $this->_ = $data['_'] ?? '';
        $this->type = $data['type'] ?? null;
        $this->w = $data['w'] ?? null;
        $this->h = $data['h'] ?? null;
        $this->size = $data['size'] ?? null;
        $this->sizes = $data['sizes'] ?? null;

        if (isset($data['bytes']['bytes'])) {
            $this->bytes = $data['bytes']['bytes'];
        } else {
            $this->bytes = $data['bytes'] ?? null;
        }

        $this->raw = $data;
    }
}
