<?php

namespace App\Modules\Telegram\DTO\Response\Messages;

class MessageEntityDTO
{
    public string $_;
    public int $offset;
    public int $length;
    public ?string $type = null;
    public ?string $url = null;
    public ?string $language = null;

    public array $raw = [];

    public function __construct(array $data)
    {
        $this->_ = $data['_'] ?? '';
        $this->offset = $data['offset'] ?? 0;
        $this->length = $data['length'] ?? 0;
        $this->type = $data['_'] ?? null;
        $this->url = $data['url'] ?? null;
        $this->language = $data['language'] ?? null;
        $this->raw = $data;
    }
}
