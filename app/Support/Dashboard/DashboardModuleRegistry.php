<?php

namespace App\Support\Dashboard;

class DashboardModuleRegistry
{
    /**
     * @var array<string, string>
     */
    private const MODULE_URLS = [
        'bluesky' => '/bluesky',
        'mastodon' => '/mastodon',
        'site-intel' => '/site-intel',
        'news-media-intel' => '/news-media-intel',
        'telegram' => '/telegram',
        'shifr' => '/shifr',
        'youtube' => '/youtube',
    ];

    /**
     * @return array<int, string>
     */
    public function keys(): array
    {
        return array_keys(self::MODULE_URLS);
    }

    public function isSupported(string $moduleKey): bool
    {
        return array_key_exists($moduleKey, self::MODULE_URLS);
    }

    public function resolveFromRouteName(string $routeName): ?string
    {
        $prefix = explode('.', $routeName)[0] ?? '';

        if (! $this->isSupported($prefix)) {
            return null;
        }

        return $prefix;
    }

    public function resolveUrl(string $moduleKey): string
    {
        return self::MODULE_URLS[$moduleKey] ?? '/dashboard';
    }
}
