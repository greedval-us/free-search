<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\MadelineProtoServiceProvider;
use App\Providers\MoonShineServiceProvider;
use App\Modules\DomainInfraIntel\Providers\DomainInfraIntelServiceProvider;
use App\Modules\DocumentIntel\Providers\DocumentIntelServiceProvider;
use App\Modules\EmailIntel\Providers\EmailIntelServiceProvider;
use App\Modules\Fio\Providers\FioServiceProvider;
use App\Modules\NewsMediaIntel\Providers\NewsMediaIntelServiceProvider;
use App\Modules\Shifr\Providers\ShifrServiceProvider;
use App\Modules\SiteIntel\Providers\SiteIntelServiceProvider;
use App\Modules\Telegram\Providers\TelegramServiceProvider;
use App\Modules\Username\Providers\UsernameServiceProvider;
use App\Modules\YouTube\Providers\YouTubeServiceProvider;

return [
    AppServiceProvider::class,
    UsernameServiceProvider::class,
    FioServiceProvider::class,
    EmailIntelServiceProvider::class,
    DocumentIntelServiceProvider::class,
    SiteIntelServiceProvider::class,
    NewsMediaIntelServiceProvider::class,
    DomainInfraIntelServiceProvider::class,
    TelegramServiceProvider::class,
    YouTubeServiceProvider::class,
    ShifrServiceProvider::class,
    FortifyServiceProvider::class,
    MadelineProtoServiceProvider::class,
    MoonShineServiceProvider::class,
];
