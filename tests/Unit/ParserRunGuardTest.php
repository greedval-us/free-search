<?php

namespace Tests\Unit;

use App\Exceptions\Public\PublicResourceNotFoundException;
use App\Exceptions\Public\PublicValidationException;
use App\Modules\ParserSupport\ParserRunGuard;
use PHPUnit\Framework\TestCase;

class ParserRunGuardTest extends TestCase
{
    public function test_missing_run_uses_explicit_public_not_found_error(): void
    {
        $this->expectException(PublicResourceNotFoundException::class);
        $this->expectExceptionMessage('errors.api.parser_run.not_found');

        (new ParserRunGuard)->requireExistingRun(null);
    }

    public function test_running_result_cannot_be_downloaded(): void
    {
        try {
            (new ParserRunGuard)->requireDownloadablePayload([
                'status' => 'running',
                'result' => null,
            ]);
            $this->fail('A running parser result must not be downloadable.');
        } catch (PublicValidationException $exception) {
            $this->assertSame(409, $exception->status());
            $this->assertSame('parser_run_not_downloadable', $exception->errorCode());
        }
    }

    public function test_completed_run_requires_result_payload(): void
    {
        $this->expectException(PublicResourceNotFoundException::class);
        $this->expectExceptionMessage('errors.api.parser_run.result_not_found');

        (new ParserRunGuard)->requireDownloadablePayload([
            'status' => 'completed',
            'result' => null,
        ]);
    }
}
