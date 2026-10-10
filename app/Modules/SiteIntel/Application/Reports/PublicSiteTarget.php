<?php

namespace App\Modules\SiteIntel\Application\Reports;

use App\Support\Domains\PublicSiteTarget as SharedPublicSiteTarget;

/** Syntax validation only; the target guard validates current DNS during every check. */
final class PublicSiteTarget
{
    public static function normalize(mixed $input): ?string
    {
        return SharedPublicSiteTarget::normalize($input);
    }
}
