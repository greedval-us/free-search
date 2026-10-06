<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoringReport extends Model
{
    protected $guarded = ['id'];

    public const FINAL_STATUSES = ['completed', 'partial', 'empty'];

    protected $hidden = ['files', 'lease_token'];

    protected function casts(): array
    {
        return ['configuration' => 'array', 'summary' => 'array', 'coverage' => 'array', 'files' => 'array', 'start_at' => 'immutable_datetime', 'end_at' => 'immutable_datetime', 'cutoff_at' => 'immutable_datetime', 'expires_at' => 'immutable_datetime', 'completed_at' => 'immutable_datetime', 'lease_until' => 'immutable_datetime', 'next_attempt_at' => 'immutable_datetime', 'dispatched_at' => 'immutable_datetime'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(MonitoringProject::class, 'project_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(MonitoringSchedule::class, 'schedule_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MonitoringReportItem::class, 'report_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function available(): bool
    {
        return in_array($this->status, self::FINAL_STATUSES, true) && $this->expires_at->isFuture();
    }
}
