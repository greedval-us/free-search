<?php

namespace App\Modules\YouTube\Analytics\Reports;

use App\Exceptions\PublicException;

final class AnalyticsReportException extends PublicException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct('youtube_analytics_reports.errors.'.$reason, 422, $reason);
    }
}
