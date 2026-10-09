<?php

namespace App\Modules\SiteIntel\Application\Reports\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final readonly class SiteIntelReportCompleted implements ShouldDispatchAfterCommit
{
    public function __construct(public int $reportId) {}
}
