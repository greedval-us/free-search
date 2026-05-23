<?php

namespace App\Modules\ParserSupport;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

abstract class JsonRunStore
{
    protected const DISK = 'private';

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function create(int $userId, array $context): array
    {
        $runId = (string) Str::uuid();
        $now = now()->toIso8601String();

        $run = $this->initialState($userId, $runId, $context, $now);
        $this->write($userId, $runId, $run);

        return $run;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(int $userId, string $runId): ?array
    {
        $path = $this->runPath($userId, $runId);
        if (!Storage::disk(static::DISK)->exists($path)) {
            return null;
        }

        $raw = Storage::disk(static::DISK)->get($path);
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param callable(array<string, mixed>): array<string, mixed> $callback
     * @return array<string, mixed>|null
     */
    public function mutate(int $userId, string $runId, callable $callback): ?array
    {
        $path = Storage::disk(static::DISK)->path($this->runPath($userId, $runId));
        if (!is_file($path)) {
            return null;
        }

        $handle = fopen($path, 'c+');
        if ($handle === false) {
            return null;
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return null;
            }

            $contents = stream_get_contents($handle);
            $run = json_decode($contents !== false ? $contents : '', true);
            if (!is_array($run)) {
                $run = [];
            }

            $run = $callback($run);
            $run['updatedAt'] = now()->toIso8601String();

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode($run, JSON_UNESCAPED_UNICODE));
            fflush($handle);
            flock($handle, LOCK_UN);

            return $run;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param array<string, mixed> $run
     */
    public function write(int $userId, string $runId, array $run): void
    {
        Storage::disk(static::DISK)->put(
            $this->runPath($userId, $runId),
            json_encode($run, JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    abstract protected function initialState(int $userId, string $runId, array $context, string $now): array;

    abstract protected function runPath(int $userId, string $runId): string;
}

