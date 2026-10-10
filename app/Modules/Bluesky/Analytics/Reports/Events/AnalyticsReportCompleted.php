<?php

namespace App\Modules\Bluesky\Analytics\Reports\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final readonly class AnalyticsReportCompleted implements ShouldDispatchAfterCommit
{
    public function __construct(public int $reportId) {}
}
