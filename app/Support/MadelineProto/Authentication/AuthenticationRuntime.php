<?php

declare(strict_types=1);

namespace App\Support\MadelineProto\Authentication;

use App\Support\MadelineProto\MadelineProtoConfig;
use Closure;
use Illuminate\Filesystem\Filesystem;

final readonly class AuthenticationRuntime
{
    public function __construct(private MadelineProtoConfig $config, private Filesystem $files) {}

    public function execute(Closure $callback): mixed
    {
        $directory = $this->config->sessionDirectoryPath().DIRECTORY_SEPARATOR.'auth-php';
        $this->files->ensureDirectoryExists($directory, 0750);
        $ini = $directory.DIRECTORY_SEPARATOR.'auth.ini';
        $contents = "zend.exception_ignore_args=1\ndisplay_errors=0\n";
        if (! $this->files->exists($ini) || $this->files->get($ini) !== $contents) {
            $this->files->replace($ini, $contents, 0600);
        }

        $scan = getenv('PHP_INI_SCAN_DIR');
        $serverScan = $_SERVER['PHP_INI_SCAN_DIR'] ?? null;
        $ignoreArgs = ini_get('zend.exception_ignore_args');
        $displayErrors = ini_get('display_errors');
        $value = ($scan === false ? '' : $scan).PATH_SEPARATOR.$directory;
        // MadelineProto passes $_SERVER to its IPC child; preserve the existing PHP scan directories.
        $_SERVER['PHP_INI_SCAN_DIR'] = $value;
        putenv('PHP_INI_SCAN_DIR='.$value);
        ini_set('zend.exception_ignore_args', '1');
        ini_set('display_errors', '0');

        try {
            if (! filter_var(ini_get('zend.exception_ignore_args'), FILTER_VALIDATE_BOOL)) {
                throw new SessionConnectionException('configuration');
            }

            return $callback();
        } finally {
            putenv($scan === false ? 'PHP_INI_SCAN_DIR' : 'PHP_INI_SCAN_DIR='.$scan);
            if ($serverScan === null) {
                unset($_SERVER['PHP_INI_SCAN_DIR']);
            } else {
                $_SERVER['PHP_INI_SCAN_DIR'] = $serverScan;
            }
            ini_set('zend.exception_ignore_args', (string) $ignoreArgs);
            ini_set('display_errors', (string) $displayErrors);
        }
    }
}
