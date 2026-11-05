<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramAnalyticsReport extends ScheduledAnalyticsReport
{
    public function targetColumn(): string
    {
        return 'chat_username';
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(TelegramAnalyticsSchedule::class, 'schedule_id')->withTrashed();
    }
}
