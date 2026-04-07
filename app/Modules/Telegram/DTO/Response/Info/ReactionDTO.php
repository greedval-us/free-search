<?php

namespace App\Modules\Telegram\DTO\Response\Info;

class ReactionDTO
{
    public string $_;
    public string $emoticon;
    public ?int $document_id = null;
    public array $raw = [];

    public function __construct(array $data)
    {
        $this->_ = $data['_'] ?? '';
        $this->emoticon = $data['emoticon'] ?? '';
        $this->document_id = isset($data['document_id']) ? (int) $data['document_id'] : null;
        $this->raw = $data;
    }
}
