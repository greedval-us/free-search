<?php

namespace App\Modules\SiteIntel\Application\Reports;

use App\Exceptions\PublicException;

final class ReportException extends PublicException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct('site_intel_reports.errors.'.$reason, 422, $reason);
    }
}
