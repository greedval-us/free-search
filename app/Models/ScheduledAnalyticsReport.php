<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

abstract class ScheduledAnalyticsReport extends Model
{
    public const PENDING = 'pending';

    public const PROCESSING = 'processing';

    public const COMPLETED = 'completed';

    public const FAILED = 'failed';

    protected $guarded = ['id'];

    protected $hidden = [
        'data', 'lease_token', 'lease_until', 'quota_charged', 'quota_charged_at',
        'quota_receipt_id', 'completion_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'scheduled_for' => 'immutable_datetime',
            'date_from' => 'immutable_datetime',
            'date_to' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'lease_until' => 'immutable_datetime',
            'available_at' => 'immutable_datetime',
            'attempt_count' => 'integer',
            'quota_charged' => 'boolean',
            'is_manual' => 'boolean',
            'quota_charged_at' => 'immutable_datetime',
            'completion_notified_at' => 'immutable_datetime',
        ];
    }

    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    public function scopeHistoryForUser(Builder $query, int $userId): void
    {
        $query->forUser($userId)->with('schedule')
            ->select([
                'id', 'schedule_id', 'user_id', $this->targetColumn(), 'scheduled_for',
                'date_from', 'date_to', 'status', 'error_code', 'completed_at',
            ])->latest('id');
    }

    abstract public function targetColumn(): string;

    public function scopeLeaseAvailable(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->whereNull('lease_until')->orWhere('lease_until', '<=', now()));
    }

    public function scopeAvailableForDispatch(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->whereNull('available_at')->orWhere('available_at', '<=', now()))
            ->leaseAvailable();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    abstract public function schedule(): BelongsTo;
}
