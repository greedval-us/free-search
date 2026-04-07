<?php

namespace App\Modules\Telegram\DTO\Response\Info;

class AvailableReactionsDTO
{
    public string $_ = '';
    public array $hash = [];
    /** @var ReactionDTO[] */
    public array $reactions = [];
    public array $recent_reactions = [];
    public array $raw = [];

    public function __construct(array $data)
    {
        $this->_ = $data['_'] ?? '';
        $this->hash = is_array($data['hash'] ?? null) ? $data['hash'] : [];
        foreach ($data['reactions'] ?? [] as $reaction) {
            $this->reactions[] = new ReactionDTO((array) $reaction);
        }
        $this->recent_reactions = is_array($data['recent_reactions'] ?? null)
            ? $data['recent_reactions']
            : [];
        $this->raw = $data;
    }
}
