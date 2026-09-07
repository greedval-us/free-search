<?php

namespace App\Support\Http;

final class RouteThrottle
{
    public const PASSWORD_UPDATE = 'throttle:6,1';

    public const TOOL_OPERATION = 'throttle:90,1';

    public const TELEGRAM_SEARCH = 'throttle:90,1';

    public const TELEGRAM_MEDIA = 'throttle:120,1';

    public const STANDARD_SEARCH = 'throttle:30,1';

    public const FEDIVERSE_SEARCH = 'throttle:45,1';

    public const DETAIL_LOOKUP = 'throttle:60,1';

    public const SITE_LOOKUP = 'throttle:90,1';

    public const ANALYTICS_SUMMARY = 'throttle:20,1';

    public const FEDIVERSE_ANALYTICS_SUMMARY = 'throttle:30,1';

    public const SITE_ANALYTICS = 'throttle:60,1';

    public const ANALYTICS_REPORT = 'throttle:10,1';

    public const FEDIVERSE_ANALYTICS_REPORT = 'throttle:20,1';

    public const SITE_REPORT = 'throttle:30,1';

    public const PARSER_START = 'throttle:10,1';

    public const PARSER_STATUS = 'throttle:40,1';

    public const PARSER_CONTROL = 'throttle:20,1';

    public const PARSER_DOWNLOAD = 'throttle:10,1';
}
