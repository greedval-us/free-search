<?php

namespace App\Http\Requests\YouTube\Concerns;

use App\Modules\YouTube\Support\YouTubeModuleConfig;

trait ResolvesYouTubeModuleConfig
{
    private function youtubeModuleConfig(): YouTubeModuleConfig
    {
        /** @var YouTubeModuleConfig|null $config */
        static $config = null;

        return $config ??= YouTubeModuleConfig::fromArray(
            (array) config('osint.youtube', []),
            (string) config('app.timezone', 'UTC')
        );
    }
}
