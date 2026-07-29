<?php

namespace App\Support\MadelineProto;

use App\Exceptions\Public\IntegrationMisconfiguredException;

final class MadelineProtoConfig
{
    private const DEFAULT_SESSION_NAME = 'default';

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
        return $this->sessionFilePathFor(self::DEFAULT_SESSION_NAME);
    }

    public function sessionDirectoryPath(): string
    {
        $base = trim($this->sessionPath);
        $base = str_replace('\\', '/', $base);
        $base = trim($base, '/');

        return storage_path($base);
    }

    public function sessionFilePathFor(string $sessionName): string
    {
        $normalized = $this->normalizeSessionName($sessionName);

        if ($normalized === self::DEFAULT_SESSION_NAME) {
            return $this->sessionDirectoryPath() . DIRECTORY_SEPARATOR . 'session.madeline';
        }

        return $this->sessionDirectoryPath() . DIRECTORY_SEPARATOR . sprintf('session.%s.madeline', $normalized);
    }

    public function logFilePath(): string
    {
        return $this->logFilePathFor(self::DEFAULT_SESSION_NAME);
    }

    public function logFilePathFor(string $sessionName): string
    {
        $path = trim($this->logPath);
        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        $basePath = storage_path($path);
        $normalized = $this->normalizeSessionName($sessionName);

        if ($normalized === self::DEFAULT_SESSION_NAME) {
            return $basePath;
        }

        $extension = pathinfo($basePath, PATHINFO_EXTENSION);
        $filename = pathinfo($basePath, PATHINFO_FILENAME);
        $directory = dirname($basePath);

        if ($extension === '') {
            return $directory . DIRECTORY_SEPARATOR . sprintf('%s-%s', $filename, $normalized);
        }

        return $directory . DIRECTORY_SEPARATOR . sprintf('%s-%s.%s', $filename, $normalized, $extension);
    }

    public function sessionPoolStateFilePath(): string
    {
        return $this->sessionDirectoryPath() . DIRECTORY_SEPARATOR . 'session-pool.state';
    }

    public function normalizeSessionName(string $sessionName): string
    {
        $normalized = strtolower(trim($sessionName));
        $normalized = preg_replace('/[^a-z0-9_-]+/', '-', $normalized) ?? '';
        $normalized = trim($normalized, '-_');

        return $normalized !== '' ? $normalized : self::DEFAULT_SESSION_NAME;
    }

    public function sessionNameFromFilePath(string $path): ?string
    {
        $basename = basename($path);

        if ($basename === 'session.madeline') {
            return self::DEFAULT_SESSION_NAME;
        }

        if (!preg_match('/^session\.([a-z0-9_-]+)\.madeline$/i', $basename, $matches)) {
            return null;
        }

        return $this->normalizeSessionName((string) $matches[1]);
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
