<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\MadelineProto\Authentication\AuthenticationRuntime;
use App\Support\MadelineProto\MadelineProtoConfig;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class AuthenticationRuntimeTest extends TestCase
{
    #[Test]
    public function authentication_hides_exception_arguments_in_current_and_child_php_and_restores_environment(): void
    {
        $directory = 'framework/testing/auth-runtime-'.Str::uuid();
        $files = new Filesystem;
        $config = MadelineProtoConfig::fromArray(['session_path' => $directory]);
        $runtime = new AuthenticationRuntime($config, $files);
        $environment = getenv('PHP_INI_SCAN_DIR');
        $serverEnvironment = $_SERVER['PHP_INI_SCAN_DIR'] ?? null;
        $ignoreArgs = ini_get('zend.exception_ignore_args');
        $displayErrors = ini_get('display_errors');

        try {
            $runtime->execute(function (): void {
                self::assertSame('1', ini_get('zend.exception_ignore_args'));
                self::assertSame('0', ini_get('display_errors'));
                $child = new Process([PHP_BINARY, '-r', 'echo (int) filter_var(ini_get("zend.exception_ignore_args"), FILTER_VALIDATE_BOOL), ":", (int) filter_var(ini_get("display_errors"), FILTER_VALIDATE_BOOL);']);
                $child->mustRun();
                self::assertSame('1:0', trim($child->getOutput()));
                throw new RuntimeException('Test interruption');
            });
            self::fail('The test interruption must propagate.');
        } catch (RuntimeException $exception) {
            if ($exception::class !== RuntimeException::class) {
                throw $exception;
            }
            self::assertSame('Test interruption', $exception->getMessage());
        } finally {
            $files->deleteDirectory(storage_path($directory));
        }

        self::assertSame($environment, getenv('PHP_INI_SCAN_DIR'));
        self::assertSame($serverEnvironment, $_SERVER['PHP_INI_SCAN_DIR'] ?? null);
        self::assertSame($ignoreArgs, ini_get('zend.exception_ignore_args'));
        self::assertSame($displayErrors, ini_get('display_errors'));
    }
}
