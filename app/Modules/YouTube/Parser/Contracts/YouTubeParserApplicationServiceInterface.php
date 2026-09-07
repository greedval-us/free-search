<?php

namespace App\Modules\YouTube\Parser\Contracts;

use App\Modules\YouTube\DTO\Request\YouTubeCommentsQueryDTO;
use App\Modules\YouTube\DTO\Request\YouTubeParserStartDTO;
use App\Modules\YouTube\DTO\Result\YouTubeCommentsResultDTO;
use App\Modules\YouTube\DTO\Result\YouTubeParserRunStatusDTO;
use App\Modules\ParserSupport\Contracts\ParserRunApplicationServiceInterface;

interface YouTubeParserApplicationServiceInterface extends ParserRunApplicationServiceInterface
{
    public function comments(YouTubeCommentsQueryDTO $query): YouTubeCommentsResultDTO;

    public function start(YouTubeParserStartDTO $input): YouTubeParserRunStatusDTO;

    public function status(int $userId, string $runId): ?YouTubeParserRunStatusDTO;

    public function stop(int $userId, string $runId): ?YouTubeParserRunStatusDTO;

}
