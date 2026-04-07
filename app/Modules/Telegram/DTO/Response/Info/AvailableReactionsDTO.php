<?php

namespace App\Modules\Telegram\DTO\Response\Info;

class AvailableReactionsDTO
{
    /** @var ReactionDTO[] */
    public array $reactions = [];

    public function __construct(array $data)
    {
        foreach ($data['reactions'] ?? [] as $reaction) {
            $this->reactions[] = new ReactionDTO($reaction);
        }
    }
}
