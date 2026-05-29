<?php

namespace App\Modules\SiteIntel\Enums;

enum SiteIntelRecommendationPriority: string
{
    case Critical = 'critical';
    case Medium = 'medium';
    case Low = 'low';
}

