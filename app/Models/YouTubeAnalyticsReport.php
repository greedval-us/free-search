<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YouTubeAnalyticsReport extends ScheduledAnalyticsReport
{
    protected $table = 'youtube_analytics_reports';

    public function targetColumn(): string
    {
        return 'channel_input';
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(YouTubeAnalyticsSchedule::class, 'schedule_id')->withTrashed();
    }
}
