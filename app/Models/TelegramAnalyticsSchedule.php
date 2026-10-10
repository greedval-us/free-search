<?php

namespace App\Models;

use App\Support\Reports\Scheduling\ReportInterval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TelegramAnalyticsSchedule extends Model
{
    use SoftDeletes;

    public const INTERVALS = ReportInterval::VALUES;

    protected $guarded = ['id'];

    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    protected function casts(): array
    {
        return ['groups' => 'array', 'enabled' => 'boolean', 'send_to_bot' => 'boolean',
            'next_run_at' => 'immutable_datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(TelegramAnalyticsReport::class, 'schedule_id');
    }
}
