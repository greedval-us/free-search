<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsMediaScheduledReport extends Model
{
    public const PENDING = 'pending';

    public const PROCESSING = 'processing';

    public const COMPLETED = 'completed';

    public const FAILED = 'failed';

    protected $guarded = ['id'];

    protected $hidden = ['data', 'lease_token', 'lease_until', 'completion_notified_at'];

    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    public function scopeLeaseAvailable(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->whereNull('lease_until')->orWhere('lease_until', '<=', now()));
    }

    protected function casts(): array
    {
        return ['data' => 'array', 'competitors' => 'array', 'search_options' => 'array',
            'scheduled_for' => 'immutable_datetime', 'completed_at' => 'immutable_datetime',
            'lease_until' => 'immutable_datetime', 'available_at' => 'immutable_datetime',
            'attempt_count' => 'integer', 'is_manual' => 'boolean', 'completion_notified_at' => 'immutable_datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(NewsMediaReportSchedule::class, 'schedule_id')->withTrashed();
    }
}
