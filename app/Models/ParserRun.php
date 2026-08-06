<?php

namespace App\Models;

use App\Modules\ParserSupport\Enums\ParserRunStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParserRun extends Model
{
    public const STATUS_UNKNOWN = 'unknown';

    public const PROGRESS_MIN = 0;

    public const PROGRESS_MAX = 100;

    protected $fillable = [
        'run_id',
        'user_id',
        'module',
        'status',
        'stage',
        'progress',
        'error',
        'file_disk',
        'file_path',
        'file_size_bytes',
        'started_at',
        'last_activity_at',
        'finished_at',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'progress' => 'integer',
            'file_size_bytes' => 'integer',
            'started_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'finished_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeExpired(Builder $query, ?CarbonInterface $at = null): Builder
    {
        return $query
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $at ?? now());
    }

    public static function normalizeProgress(mixed $value): int
    {
        return max(self::PROGRESS_MIN, min(self::PROGRESS_MAX, (int) $value));
    }

    /**
     * @return list<string>
     */
    public static function terminalStatuses(): array
    {
        return array_values(array_map(
            static fn (ParserRunStatus $status): string => $status->value,
            array_filter(
                ParserRunStatus::cases(),
                static fn (ParserRunStatus $status): bool => $status->isTerminal()
            )
        ));
    }

    /**
     * @return list<string>
     */
    public static function downloadableStatuses(): array
    {
        return array_values(array_map(
            static fn (ParserRunStatus $status): string => $status->value,
            array_filter(
                ParserRunStatus::cases(),
                static fn (ParserRunStatus $status): bool => $status->isDownloadable()
            )
        ));
    }

    public static function normalizeStatus(mixed $value): string
    {
        return ParserRunStatus::tryFrom((string) $value)?->value ?? self::STATUS_UNKNOWN;
    }

    public static function isTerminalStatus(mixed $value): bool
    {
        return ParserRunStatus::tryFrom((string) $value)?->isTerminal() ?? false;
    }

    public static function isDownloadableStatus(mixed $value): bool
    {
        return ParserRunStatus::tryFrom((string) $value)?->isDownloadable() ?? false;
    }
}
