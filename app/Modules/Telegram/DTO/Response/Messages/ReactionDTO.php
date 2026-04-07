<?php

namespace App\Modules\Telegram\DTO\Response\Messages;

class ReactionDTO
{
    public ?string $emoticon = null;
    public ?int $count = null;
    public ?string $reaction_type = null;
    public ?int $document_id = null;
    public bool $is_paid = false;
    public ?string $display = null;

    public array $raw = [];

    public function __construct(array $data)
    {
        $this->raw = $data;

        $reaction = $data['reaction'] ?? null;
        if (is_array($reaction)) {
            $this->reaction_type = $reaction['_'] ?? null;
            $this->emoticon = $reaction['emoticon'] ?? null;
            $this->document_id = isset($reaction['document_id']) ? (int) $reaction['document_id'] : null;
            $this->is_paid = ($reaction['_'] ?? null) === 'reactionPaid';

            if ($this->is_paid && $this->emoticon === null) {
                $this->emoticon = '⭐';
            }
        } elseif (is_string($reaction)) {
            $this->emoticon = $reaction;
        }

        $this->count = isset($data['count']) ? (int) $data['count'] : null;

        $type = strtolower((string) ($this->reaction_type ?? ''));
        if ($this->is_paid || str_contains($type, 'paid')) {
            $this->display = '⭐';
        } elseif ($this->emoticon !== null && trim($this->emoticon) !== '') {
            $this->display = trim($this->emoticon);
        } elseif (str_contains($type, 'custom')) {
            $this->display = '✨';
        }
    }
}
