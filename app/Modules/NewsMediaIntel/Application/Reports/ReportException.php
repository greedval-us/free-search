<?php

namespace App\Modules\NewsMediaIntel\Application\Reports;

use App\Exceptions\PublicException;

final class ReportException extends PublicException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct('news_media_reports.errors.'.$reason, 422, $reason);
    }
}
