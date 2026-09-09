<?php

namespace App\Modules\Telegram\Parser\Contracts;

use App\Modules\ParserSupport\Contracts\ParserRunApplicationServiceInterface;
use App\Modules\Telegram\DTO\Request\TelegramParserStartDTO;
use App\Modules\Telegram\DTO\Result\TelegramParserRunStatusDTO;

interface TelegramParserApplicationServiceInterface extends ParserRunApplicationServiceInterface
{
    public function start(TelegramParserStartDTO $input): TelegramParserRunStatusDTO;

    public function status(int $userId, string $runId): ?TelegramParserRunStatusDTO;

    public function stop(int $userId, string $runId): ?TelegramParserRunStatusDTO;
}
