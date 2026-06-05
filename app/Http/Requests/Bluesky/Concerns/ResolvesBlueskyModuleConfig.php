<?php

namespace App\Http\Requests\Bluesky\Concerns;

use App\Modules\Bluesky\Support\BlueskyModuleConfig;

trait ResolvesBlueskyModuleConfig
{
    protected function blueskyModuleConfig(): BlueskyModuleConfig
    {
        return app(BlueskyModuleConfig::class);
    }
}
