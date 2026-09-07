<?php

namespace App\Modules\Bluesky\Parser\Contracts;

use App\Modules\Bluesky\DTO\Request\BlueskyParserStartDTO;
use App\Modules\Bluesky\DTO\Result\BlueskyParserRunStatusDTO;
use App\Modules\ParserSupport\Contracts\ParserRunApplicationServiceInterface;

interface BlueskyParserApplicationServiceInterface extends ParserRunApplicationServiceInterface
{
    public function start(BlueskyParserStartDTO $input): BlueskyParserRunStatusDTO;

    public function status(int $userId, string $runId): ?BlueskyParserRunStatusDTO;

    public function stop(int $userId, string $runId): ?BlueskyParserRunStatusDTO;

}
