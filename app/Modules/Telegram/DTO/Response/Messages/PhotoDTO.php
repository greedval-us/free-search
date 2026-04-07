<?php

namespace App\Services\Telegram\DTO\Response\Messages;

class PhotoDTO
{
    public string $_;
    public bool $has_stickers = false;
    public int $id = 0;
    public ?int $access_hash = null;
    public ?string $file_reference = null;
    public ?int $date = null;

    /** @var PhotoSizeDTO[] */
    public array $sizes = [];

    public ?int $dc_id = null;
    public array $raw = [];

    public function __construct(array $data)
    {
        $this->_ = $data['_'] ?? '';
        $this->has_stickers = $data['has_stickers'] ?? false;
        $this->id = $data['id'] ?? 0;
        $this->access_hash = $data['access_hash'] ?? null;

        if (isset($data['file_reference']['bytes'])) {
            $this->file_reference = $data['file_reference']['bytes'];
        } else {
            $this->file_reference = $data['file_reference'] ?? null;
        }

        $this->date = $data['date'] ?? null;

        foreach ($data['sizes'] ?? [] as $size) {
            $this->sizes[] = new PhotoSizeDTO((array)$size);
        }

        $this->dc_id = $data['dc_id'] ?? null;
        $this->raw = $data;
    }
}
