<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class HttpAutoloadTest extends TestCase
{
    public function test_http_startup_does_not_prefix_json_with_dependency_output(): void
    {
        $root = dirname(__DIR__, 2);
        $process = new Process([
            PHP_BINARY,
            '-d',
            'error_log=',
            '-r',
            'require "bootstrap/http-autoload.php"; echo json_encode(["booted" => class_exists(Illuminate\\Foundation\\Application::class)]);',
        ], $root);
        $process->mustRun();

        $this->assertSame(['booted' => true], json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR));

        if (PHP_OS_FAMILY === 'Windows') {
            $this->assertStringContainsString('MadelineProto', $process->getErrorOutput());
        }
    }
}
