<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SiteIntelReportSchedule extends Model
{
    use SoftDeletes;

    protected $table = 'site_intel_report_schedules';

    public const INTERVALS = ['1', '3', '7', 'month'];

    public const TYPES = ['analytics', 'seo-audit'];

    public const PLATFORM_TYPES = ['auto', 'generic', 'media-platform', 'content-site', 'storefront'];

    protected $guarded = ['id'];

    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    protected function casts(): array
    {
        return ['targets' => 'array', 'crawl_limit' => 'integer', 'enabled' => 'boolean', 'send_to_bot' => 'boolean',
            'next_run_at' => 'immutable_datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(SiteIntelScheduledReport::class, 'schedule_id');
    }
}
