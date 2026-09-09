<?php

namespace App\Modules\TelegramBot\Domain\Contracts;

use App\Modules\TelegramBot\Domain\DTO\BotDocument;
use Illuminate\Contracts\Pagination\Paginator;

interface ArtifactProvider
{
    public function key(): string;

    /** @return Paginator<int, array{id: int, label: string, formats: list<string>}> */
    public function listing(int $userId, int $page): Paginator;

    public function document(int $userId, int $id, string $format, string $locale): BotDocument;
}
