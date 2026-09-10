<?php

declare(strict_types=1);

namespace App\Support\MadelineProto\Authentication;

use App\Support\MadelineProto\MadelineProtoConfig;
use Closure;
use Illuminate\Filesystem\Filesystem;

final readonly class SessionFiles
{
    public function __construct(private MadelineProtoConfig $config, private Filesystem $files) {}

    public function locked(string $name, Closure $callback): mixed
    {
        return $this->withLock('session:'.$this->config->normalizeSessionName($name), $callback);
    }

    public function catalogLocked(Closure $callback): mixed
    {
        return $this->withLock('catalog', $callback);
    }

    private function withLock(string $name, Closure $callback): mixed
    {
        $directory = $this->config->sessionDirectoryPath();
        $this->files->ensureDirectoryExists($directory, 0750);
        $key = hash('sha256', $name);
        $handle = fopen($directory.DIRECTORY_SEPARATOR.$key.'.auth-lock', 'c');
        if ($handle === false) {
            throw new SessionConnectionException('storage');
        }

        try {
            if (! flock($handle, LOCK_EX | LOCK_NB)) {
                throw new SessionConnectionException('busy');
            }

            return $callback();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function reserve(string $name): void
    {
        if ($this->exists($name) || $this->pending($name)) {
            throw new SessionConnectionException('exists');
        }

        $handle = fopen($this->config->pendingSessionMarkerPath($name), 'x');
        if ($handle === false) {
            throw new SessionConnectionException('storage');
        }
        fclose($handle);
    }

    public function exists(string $name): bool
    {
        return file_exists($this->config->sessionFilePathFor($name));
    }

    public function pending(string $name): bool
    {
        return file_exists($this->config->pendingSessionMarkerPath($name));
    }

    public function publish(string $name): void
    {
        if (! $this->exists($name)) {
            throw new SessionConnectionException('storage');
        }
        if ($this->pending($name) && ! unlink($this->config->pendingSessionMarkerPath($name))) {
            throw new SessionConnectionException('storage');
        }
    }

    public function quarantine(string $name): void
    {
        if (! $this->pending($name)) {
            $handle = fopen($this->config->pendingSessionMarkerPath($name), 'x');
            if ($handle === false) {
                throw new SessionConnectionException('storage');
            }
            fclose($handle);
        }
    }
}
