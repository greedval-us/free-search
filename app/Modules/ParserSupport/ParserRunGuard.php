<?php

namespace App\Modules\ParserSupport;

use App\Models\ParserRun;

class ParserRunGuard
{
    /**
     * @param array<string, mixed>|null $run
     * @return array<string, mixed>
     */
    public function requireExistingRun(?array $run): array
    {
        abort_unless($run !== null, 404);

        return $run;
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    public function requireDownloadablePayload(array $run): array
    {
        abort_unless(ParserRun::isDownloadableStatus($run['status'] ?? null), 409);

        $payload = is_array($run['result'] ?? null) ? $run['result'] : null;
        abort_unless($payload !== null, 404);

        return $payload;
    }
}
