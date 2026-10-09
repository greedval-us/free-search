<?php

namespace App\Modules\Bluesky\Analytics\Reports;

use App\Exceptions\PublicException;

final class AnalyticsReportException extends PublicException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct('bluesky_analytics_reports.errors.'.$reason, 422, $reason);
    }
}
