<?php

namespace App\Modules\ParserSupport;

use App\Modules\ParserSupport\Enums\ParserRunStatus;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JsonException;
use UnexpectedValueException;

abstract class JsonRunStore
{
    protected const DISK = 'private';

    private const INITIAL_PROGRESS = 1;

    private const INITIAL_ADVANCE_TIMESTAMP = 0;

    public function __construct(
        private readonly ParserRunMetadataSynchronizer $metadataSynchronizer,
        private readonly ParserRunFileStorage $files,
    ) {}

    /**
     * @param  array<string, mixed>  $context
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
        if (! is_string($raw)) {
            return null;
        }

        try {
            $decoded = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            Log::warning('Unable to decode parser run JSON.', [
                'module' => $this->moduleKey(),
                'user_id' => $userId,
                'run_id' => $runId,
                'exception' => $exception::class,
            ]);

            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  callable(array<string, mixed>): array<string, mixed>  $callback
     * @return array<string, mixed>|null
     */
    public function mutate(int $userId, string $runId, callable $callback): ?array
    {
        $relativePath = $this->runPath($userId, $runId);
        $path = $this->disk()->path($relativePath);

        return $this->files->withExclusiveLock($path, function () use ($userId, $runId, $relativePath, $path, $callback): ?array {
            if (! is_file($path)) {
                return null;
            }

            $contents = $this->disk()->get($relativePath);
            $run = json_decode($contents ?? '', true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($run)) {
                throw new UnexpectedValueException("Parser run [{$runId}] does not contain a JSON object.");
            }

            $run = $callback($run);
            $run['updatedAt'] = now()->toIso8601String();

            $this->files->replace($path, $this->encodeRun($run));
            // Keep file and metadata writes ordered under the same exclusive lock.
            $this->syncMetadata($userId, $runId, $run, $relativePath);

            return $run;
        });
    }

    /**
     * @param  array<string, mixed>  $run
     */
    public function write(int $userId, string $runId, array $run): void
    {
        $path = $this->runPath($userId, $runId);
        $absolutePath = $this->disk()->path($path);
        $this->files->withExclusiveLock($absolutePath, function () use ($userId, $runId, $path, $absolutePath, $run): void {
            $this->files->replace($absolutePath, $this->encodeRun($run));
            $this->syncMetadata($userId, $runId, $run, $path);
        });
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    abstract protected function initialState(int $userId, string $runId, array $context, string $now): array;

    abstract protected function moduleKey(): string;

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $cursor
     * @param  array<string, int>  $stats
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    final protected function buildInitialState(
        int $userId,
        string $runId,
        string $now,
        string $stage,
        array $context,
        array $cursor,
        array $stats,
        array $data,
    ): array {
        return [
            'runId' => $runId,
            'userId' => $userId,
            'status' => ParserRunStatus::Running->value,
            'stage' => $stage,
            'progress' => self::INITIAL_PROGRESS,
            'error' => null,
            'createdAt' => $now,
            'updatedAt' => $now,
            'context' => $context,
            'cursor' => [
                'nextAdvanceAt' => self::INITIAL_ADVANCE_TIMESTAMP,
                ...$cursor,
            ],
            'stats' => $stats,
            'data' => $data,
            'result' => null,
        ];
    }

    final protected function runPath(int $userId, string $runId): string
    {
        return sprintf('%s-parser-runs/%d/%s.json', $this->moduleKey(), $userId, $runId);
    }

    /**
     * @param  array<string, mixed>  $run
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

    private function disk(): FilesystemAdapter
    {
        return Storage::disk(static::DISK);
    }

    /**
     * @param  array<string, mixed>  $run
     */
    private function encodeRun(array $run): string
    {
        return json_encode($run, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
