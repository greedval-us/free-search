<?php

namespace App\Http\Requests\YouTube\Concerns;

use App\Modules\YouTube\Support\YouTubeModuleConfig;

trait ResolvesYouTubeModuleConfig
{
    private function youtubeModuleConfig(): YouTubeModuleConfig
    {
        return app(YouTubeModuleConfig::class);
    }
}
