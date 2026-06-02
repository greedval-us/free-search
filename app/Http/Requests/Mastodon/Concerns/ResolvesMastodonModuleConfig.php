<?php

namespace App\Http\Requests\Mastodon\Concerns;

use App\Modules\Mastodon\Support\MastodonModuleConfig;

trait ResolvesMastodonModuleConfig
{
    protected function mastodonModuleConfig(): MastodonModuleConfig
    {
        return app(MastodonModuleConfig::class);
    }
}
