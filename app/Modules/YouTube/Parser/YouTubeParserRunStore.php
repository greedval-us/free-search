<?php

namespace App\Modules\YouTube\Parser;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class YouTubeParserRunStore
{
    private const DISK = 'private';

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function create(int $userId, array $context): array
    {
        $runId = (string) Str::uuid();
        $now = now()->toIso8601String();

        $run = [
            'runId' => $runId,
            'userId' => $userId,
            'status' => 'running',
            'stage' => 'comments',
            'progress' => 1,
            'error' => null,
            'createdAt' => $now,
            'updatedAt' => $now,
            'context' => $context,
            'cursor' => [
                'commentsPageToken' => '',
                'commentsPage' => 0,
                'commentsTotalHint' => 0,
                'replyThreadIds' => [],
                'replyThreadIndex' => 0,
                'replyPageToken' => '',
                'nextAdvanceAt' => 0,
            ],
            'stats' => [
                'processedComments' => 0,
                'processedReplies' => 0,
            ],
            'data' => [
                'commentIds' => [],
                'replyIds' => [],
                'threadParentMap' => [],
                'commentsIndex' => [],
                'repliesIndex' => [],
            ],
            'result' => null,
        ];

        $this->write($userId, $runId, $run);

        return $run;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(int $userId, string $runId): ?array
    {
        $path = $this->path($userId, $runId);
        if (!Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        $raw = Storage::disk(self::DISK)->get($path);
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param callable(array<string, mixed>): array<string, mixed> $callback
     * @return array<string, mixed>|null
     */
    public function mutate(int $userId, string $runId, callable $callback): ?array
    {
        $path = Storage::disk(self::DISK)->path($this->path($userId, $runId));
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
        Storage::disk(self::DISK)->put($this->path($userId, $runId), json_encode($run, JSON_UNESCAPED_UNICODE));
    }

    private function path(int $userId, string $runId): string
    {
        return sprintf('youtube-parser-runs/%d/%s.json', $userId, $runId);
    }
}

