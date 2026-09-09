<?php

namespace App\Modules\ParserSupport;

use Closure;
use RuntimeException;

final class ParserRunFileStorage
{
    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function withExclusiveLock(string $path, Closure $callback): mixed
    {
        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create parser storage directory.');
        }

        // A stable per-user lock must not be replaced together with the JSON inode.
        $handle = fopen($directory.'/.lock', 'c');
        if ($handle === false) {
            throw new RuntimeException('Unable to open parser storage lock.');
        }

        try {
            if (! flock($handle, LOCK_EX)) {
                throw new RuntimeException('Unable to lock parser storage.');
            }

            return $callback();
        } finally {
            fclose($handle);
        }
    }

    public function replace(string $path, string $contents): void
    {
        $temporaryPath = tempnam(dirname($path), '.parser-');
        if ($temporaryPath === false) {
            throw new RuntimeException('Unable to create temporary parser state.');
        }

        try {
            if (file_put_contents($temporaryPath, $contents) !== strlen($contents)) {
                throw new RuntimeException('Unable to write parser state.');
            }

            if (! rename($temporaryPath, $path)) {
                throw new RuntimeException('Unable to replace parser state.');
            }
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }
}
