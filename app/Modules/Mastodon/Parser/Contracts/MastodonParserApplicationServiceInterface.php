<?php

namespace App\Modules\Mastodon\Parser\Contracts;

use App\Modules\Mastodon\DTO\Request\MastodonParserStartDTO;
use App\Modules\Mastodon\DTO\Result\MastodonParserRunStatusDTO;
use App\Modules\ParserSupport\Contracts\ParserRunApplicationServiceInterface;

interface MastodonParserApplicationServiceInterface extends ParserRunApplicationServiceInterface
{
    public function start(MastodonParserStartDTO $input): MastodonParserRunStatusDTO;

    public function status(int $userId, string $runId): ?MastodonParserRunStatusDTO;

    public function stop(int $userId, string $runId): ?MastodonParserRunStatusDTO;
}
