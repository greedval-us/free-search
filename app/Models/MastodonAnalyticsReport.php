<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MastodonAnalyticsReport extends ScheduledAnalyticsReport
{
    protected $table = 'mastodon_analytics_reports';

    public function targetColumn(): string
    {
        return 'account_input';
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(MastodonAnalyticsSchedule::class, 'schedule_id')->withTrashed();
    }
}
