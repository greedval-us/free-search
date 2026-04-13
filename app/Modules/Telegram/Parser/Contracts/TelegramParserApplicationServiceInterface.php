<?php

namespace App\Modules\Telegram\Parser\Contracts;

use App\Modules\Telegram\DTO\Request\TelegramParserStartDTO;
use App\Modules\Telegram\DTO\Result\ParserRunStatusDTO;

interface TelegramParserApplicationServiceInterface
{
    public function start(TelegramParserStartDTO $input): ParserRunStatusDTO;

    public function status(int $userId, string $runId): ?ParserRunStatusDTO;

    public function stop(int $userId, string $runId): ?ParserRunStatusDTO;

    /**
     * @return array<string, mixed>
     */
    public function getDownloadPayload(int $userId, string $runId): array;
}
