<?php

namespace App\Modules\NewsMediaIntel\Application\Reports\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final readonly class NewsMediaReportCompleted implements ShouldDispatchAfterCommit
{
    public function __construct(public int $reportId) {}
}
