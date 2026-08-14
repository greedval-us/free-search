<?php

namespace App\Support\Observability;

final readonly class MoonShineSecurityConfig
{
    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            loginMaxAttempts: max(1, (int) data_get($config, 'login_throttle.max_attempts', 3)),
            loginDecaySeconds: max(15, (int) data_get($config, 'login_throttle.decay_seconds', 60)),
            alertChannel: trim((string) data_get($config, 'login_alert.channel', 'stack')) ?: 'stack',
            alertEmailEnabled: (bool) data_get($config, 'login_alert.email_enabled', false),
            alertEmail: trim((string) data_get($config, 'login_alert.email', '')),
        );
    }

    public function __construct(
        private int $loginMaxAttempts,
        private int $loginDecaySeconds,
        private string $alertChannel,
        private bool $alertEmailEnabled,
        private string $alertEmail,
    ) {}

    public function loginMaxAttempts(): int
    {
        return $this->loginMaxAttempts;
    }

    public function loginDecaySeconds(): int
    {
        return $this->loginDecaySeconds;
    }

    public function alertChannel(): string
    {
        return $this->alertChannel;
    }

    public function shouldSendAlertEmail(): bool
    {
        return $this->alertEmailEnabled && $this->alertEmail !== '';
    }

    public function alertEmail(): string
    {
        return $this->alertEmail;
    }
}
