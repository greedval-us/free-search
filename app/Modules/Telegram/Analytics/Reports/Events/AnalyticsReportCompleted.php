<?php

namespace App\Modules\Telegram\Analytics\Reports\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final readonly class AnalyticsReportCompleted implements ShouldDispatchAfterCommit
{
    public function __construct(public int $reportId) {}
}
