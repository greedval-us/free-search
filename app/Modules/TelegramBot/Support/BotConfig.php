<?php

namespace App\Modules\TelegramBot\Support;

use Illuminate\Contracts\Config\Repository;

final readonly class BotConfig
{
    public function __construct(private Repository $config) {}

    public function active(): bool
    {
        return (bool) $this->get('enabled') && $this->botId() > 0
            && preg_match('/^[A-Za-z0-9_]{5,32}$/D', $this->username()) === 1
            && preg_match('/^[A-Za-z0-9_-]{32,256}$/D', $this->secret()) === 1;
    }

    public function botId(): int
    {
        return (int) $this->get('bot_id');
    }

    public function username(): string
    {
        return ltrim(trim((string) $this->get('username')), '@');
    }

    public function secret(): string
    {
        return (string) $this->get('webhook_secret');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config->get('telegram_bot.'.$key, $default);
    }

    public function integer(string $key): int
    {
        return max(1, (int) $this->get($key));
    }

    public function locale(?string $locale): string
    {
        return str_starts_with(strtolower((string) $locale), 'ru') ? 'ru' : 'en';
    }

    public function siteUrl(string $path = '/dashboard'): string
    {
        // Only application-owned relative paths can appear in bot keyboards.
        if (! str_starts_with($path, '/') || str_starts_with($path, '//') || str_contains($path, '\\')) {
            throw new \InvalidArgumentException('Bot menu paths must be application-relative.');
        }

        return rtrim((string) $this->config->get('app.url'), '/').$path;
    }
}
