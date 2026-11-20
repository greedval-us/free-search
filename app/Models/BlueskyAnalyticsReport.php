<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlueskyAnalyticsReport extends ScheduledAnalyticsReport
{
    protected $table = 'bluesky_analytics_reports';

    public function targetColumn(): string
    {
        return 'account_input';
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(BlueskyAnalyticsSchedule::class, 'schedule_id')->withTrashed();
    }
}
