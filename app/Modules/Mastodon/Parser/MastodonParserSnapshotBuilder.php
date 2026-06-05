<?php

namespace App\Modules\Mastodon\Parser;

use App\Modules\Mastodon\DTO\Parser\MastodonParserCollectedDataDTO;
use App\Modules\Mastodon\DTO\Parser\MastodonParserSnapshotDTO;

final class MastodonParserSnapshotBuilder
{
    public function build(string $accountQuery, MastodonParserCollectedDataDTO $data): MastodonParserSnapshotDTO
    {
        return new MastodonParserSnapshotDTO(
            account: $accountQuery,
            resolvedAccount: $data->account(),
            statusesIndex: $data->statusesIndex(),
            commentsIndex: $data->commentsIndex(),
        );
    }
}
