<?php

use App\Modules\Bluesky\Providers\BlueskyServiceProvider;
use App\Modules\Export\Providers\ExportServiceProvider;
use App\Modules\Mastodon\Providers\MastodonServiceProvider;
use App\Modules\NewsMediaIntel\Providers\NewsMediaIntelServiceProvider;
use App\Modules\ParserSupport\Providers\ParserSupportServiceProvider;
use App\Modules\Shifr\Providers\ShifrServiceProvider;
use App\Modules\SiteIntel\Providers\SiteIntelServiceProvider;
use App\Modules\Telegram\Providers\TelegramServiceProvider;
use App\Modules\YouTube\Providers\YouTubeServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\DashboardServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\MadelineProtoServiceProvider;
use App\Providers\MoonShineServiceProvider;
use App\Providers\SecurityServiceProvider;
use App\Providers\SupportServiceProvider;

return [
    AppServiceProvider::class,
    SecurityServiceProvider::class,
    SupportServiceProvider::class,
    DashboardServiceProvider::class,
    ParserSupportServiceProvider::class,
    ExportServiceProvider::class,
    BlueskyServiceProvider::class,
    SiteIntelServiceProvider::class,
    MastodonServiceProvider::class,
    NewsMediaIntelServiceProvider::class,
    TelegramServiceProvider::class,
    YouTubeServiceProvider::class,
    ShifrServiceProvider::class,
    FortifyServiceProvider::class,
    MadelineProtoServiceProvider::class,
    MoonShineServiceProvider::class,
];
