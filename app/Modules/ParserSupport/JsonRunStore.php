<?php

namespace App\Modules\ParserSupport;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

abstract class JsonRunStore
{
    protected const DISK = 'private';

    public function __construct(
        private readonly ParserRunMetadataSynchronizer $metadataSynchronizer,
    ) {}

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
        if (! $this->disk()->exists($path)) {
            return null;
        }

        $raw = $this->disk()->get($path);
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param callable(array<string, mixed>): array<string, mixed> $callback
     * @return array<string, mixed>|null
     */
    public function mutate(int $userId, string $runId, callable $callback): ?array
    {
        $relativePath = $this->runPath($userId, $runId);
        $path = $this->disk()->path($relativePath);
        if (! is_file($path)) {
            return null;
        }

        $handle = fopen($path, 'c+');
        if ($handle === false) {
            return null;
        }

        try {
            if (! flock($handle, LOCK_EX)) {
                return null;
            }

            $contents = stream_get_contents($handle);
            $run = json_decode($contents !== false ? $contents : '', true);
            if (! is_array($run)) {
                $run = [];
            }

            $run = $callback($run);
            $run['updatedAt'] = now()->toIso8601String();

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, $this->encodeRun($run));
            fflush($handle);
            flock($handle, LOCK_UN);
            $this->syncMetadata($userId, $runId, $run, $relativePath);

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
        $path = $this->runPath($userId, $runId);

        $this->disk()->put($path, $this->encodeRun($run));

        $this->syncMetadata($userId, $runId, $run, $path);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    abstract protected function initialState(int $userId, string $runId, array $context, string $now): array;

    abstract protected function moduleKey(): string;

    abstract protected function runPath(int $userId, string $runId): string;

    /**
     * @param array<string, mixed> $run
     */
    private function syncMetadata(int $userId, string $runId, array $run, ?string $path = null): void
    {
        $normalizedRun = $run;
        $normalizedRun['runId'] ??= $runId;

        $this->metadataSynchronizer->sync(
            $this->moduleKey(),
            $userId,
            static::DISK,
            $path ?? $this->runPath($userId, $runId),
            $normalizedRun
        );
    }

    private function disk()
    {
        return Storage::disk(static::DISK);
    }

    /**
     * @param array<string, mixed> $run
     */
    private function encodeRun(array $run): string
    {
        return json_encode($run, JSON_UNESCAPED_UNICODE);
    }
}
