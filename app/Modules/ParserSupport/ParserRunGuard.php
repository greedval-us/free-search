<?php

namespace App\Modules\ParserSupport;

use App\Exceptions\Public\PublicResourceNotFoundException;
use App\Exceptions\Public\PublicValidationException;
use App\Models\ParserRun;

class ParserRunGuard
{
    /**
     * @param array<string, mixed>|null $run
     * @return array<string, mixed>
     */
    public function requireExistingRun(?array $run): array
    {
        if ($run === null) {
            throw new PublicResourceNotFoundException(
                'errors.api.parser_run.not_found',
                'parser_run_not_found',
            );
        }

        return $run;
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    public function requireDownloadablePayload(array $run): array
    {
        if (! ParserRun::isDownloadableStatus($run['status'] ?? null)) {
            throw new PublicValidationException(
                'errors.api.parser_run.not_downloadable',
                'parser_run_not_downloadable',
                409,
            );
        }

        $payload = is_array($run['result'] ?? null) ? $run['result'] : null;
        if ($payload === null) {
            throw new PublicResourceNotFoundException(
                'errors.api.parser_run.result_not_found',
                'parser_run_result_not_found',
            );
        }

        return $payload;
    }
}
