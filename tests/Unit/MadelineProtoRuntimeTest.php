<?php

namespace Tests\Unit;

use App\Support\MadelineProto\MadelineProtoRuntime;
use RuntimeException;
use Tests\TestCase;

class MadelineProtoRuntimeTest extends TestCase
{
    private string $runtimeDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->runtimeDirectory = storage_path('framework/testing/madeline-runtime-'.bin2hex(random_bytes(4)));
        mkdir($this->runtimeDirectory, 0777, true);
    }

    public function test_it_executes_callback_from_private_runtime_directory(): void
    {
        $originalDirectory = getcwd();
        $runtime = new MadelineProtoRuntime;

        $callbackDirectory = $runtime->executeFrom(
            $this->runtimeDirectory,
            static fn (): string|false => getcwd(),
        );

        $this->assertSame(realpath($this->runtimeDirectory), realpath((string) $callbackDirectory));
        $this->assertSame($originalDirectory, getcwd());
    }

    public function test_it_restores_working_directory_after_exception(): void
    {
        $originalDirectory = getcwd();
        $runtime = new MadelineProtoRuntime;

        try {
            $runtime->executeFrom(
                $this->runtimeDirectory,
                static fn (): never => throw new RuntimeException('Expected failure.'),
            );

            $this->fail('The callback exception was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Expected failure.', $exception->getMessage());
        }

        $this->assertSame($originalDirectory, getcwd());
    }

    protected function tearDown(): void
    {
        @rmdir($this->runtimeDirectory);

        parent::tearDown();
    }
}
