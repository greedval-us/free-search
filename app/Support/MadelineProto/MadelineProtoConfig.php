<?php

namespace App\Support\MadelineProto;

use App\Exceptions\Public\IntegrationMisconfiguredException;

final class MadelineProtoConfig
{
    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            apiId: self::nullableInt($config['api_id'] ?? null),
            apiHash: self::nullableString($config['api_hash'] ?? null),
            sessionPath: self::stringValue($config['session_path'] ?? null, 'app/private/session/'),
            logPath: self::stringValue($config['log_path'] ?? null, 'logs/madeline.log'),
        );
    }

    public function __construct(
        private readonly ?int $apiId,
        private readonly ?string $apiHash,
        private readonly string $sessionPath,
        private readonly string $logPath,
    ) {
    }

    public function apiId(): int
    {
        if ($this->apiId === null) {
            throw new IntegrationMisconfiguredException('errors.api.telegram.not_configured', 'telegram_not_configured');
        }

        return $this->apiId;
    }

    public function apiHash(): string
    {
        if ($this->apiHash === null || $this->apiHash === '') {
            throw new IntegrationMisconfiguredException('errors.api.telegram.not_configured', 'telegram_not_configured');
        }

        return $this->apiHash;
    }

    public function sessionFilePath(): string
    {
        $base = trim($this->sessionPath);
        $base = str_replace('\\', '/', $base);
        $base = trim($base, '/');

        return storage_path($base . '/session.madeline');
    }

    public function logFilePath(): string
    {
        $path = trim($this->logPath);
        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        return storage_path($path);
    }

    private static function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private static function stringValue(mixed $value, string $default): string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : $default;
    }
}
