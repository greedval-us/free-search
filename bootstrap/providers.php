<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\MadelineProtoServiceProvider;
use App\Providers\MoonShineServiceProvider;
use App\Modules\Shifr\Providers\ShifrServiceProvider;
use App\Modules\Telegram\Providers\TelegramServiceProvider;
use App\Modules\YouTube\Providers\YouTubeServiceProvider;

return [
    AppServiceProvider::class,
    TelegramServiceProvider::class,
    YouTubeServiceProvider::class,
    ShifrServiceProvider::class,
    FortifyServiceProvider::class,
    MadelineProtoServiceProvider::class,
    MoonShineServiceProvider::class,
];
