<?php

use App\Providers\AppServiceProvider;
use App\Providers\DashboardServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\MadelineProtoServiceProvider;
use App\Providers\MoonShineServiceProvider;
use App\Modules\CompanyIntel\Providers\CompanyIntelServiceProvider;
use App\Modules\DomainInfraIntel\Providers\DomainInfraIntelServiceProvider;
use App\Modules\DocumentIntel\Providers\DocumentIntelServiceProvider;
use App\Modules\EmailIntel\Providers\EmailIntelServiceProvider;
use App\Modules\Export\Providers\ExportServiceProvider;
use App\Modules\Fio\Providers\FioServiceProvider;
use App\Modules\NewsMediaIntel\Providers\NewsMediaIntelServiceProvider;
use App\Modules\Shifr\Providers\ShifrServiceProvider;
use App\Modules\SiteIntel\Providers\SiteIntelServiceProvider;
use App\Modules\Telegram\Providers\TelegramServiceProvider;
use App\Modules\Username\Providers\UsernameServiceProvider;
use App\Modules\YouTube\Providers\YouTubeServiceProvider;

return [
    AppServiceProvider::class,
    DashboardServiceProvider::class,
    CompanyIntelServiceProvider::class,
    UsernameServiceProvider::class,
    FioServiceProvider::class,
    EmailIntelServiceProvider::class,
    ExportServiceProvider::class,
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
