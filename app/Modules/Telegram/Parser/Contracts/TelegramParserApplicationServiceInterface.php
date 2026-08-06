<?php

namespace App\Modules\Telegram\Parser\Contracts;

use App\Modules\Telegram\DTO\Request\TelegramParserStartDTO;
use App\Modules\Telegram\DTO\Result\TelegramParserRunStatusDTO;

interface TelegramParserApplicationServiceInterface
{
    public function start(TelegramParserStartDTO $input): TelegramParserRunStatusDTO;

    public function status(int $userId, string $runId): ?TelegramParserRunStatusDTO;

    public function stop(int $userId, string $runId): ?TelegramParserRunStatusDTO;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function history(int $userId): array;

    /**
     * @return array<string, mixed>
     */
    public function getDownloadPayload(int $userId, string $runId): array;
}
