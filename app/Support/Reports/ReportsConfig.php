<?php

namespace App\Support\Reports;

final class ReportsConfig
{
    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config, string $timezone): self
    {
        return new self(
            timezone: trim($timezone) !== '' ? $timezone : 'UTC',
            generatedAtFormat: self::stringValue($config, 'generated_at_format', 'd.m.Y H:i'),
            filenameTimestampFormat: self::stringValue($config, 'filename_timestamp_format', 'Ymd-His'),
            downloadContentType: self::stringValue($config, 'download_content_type', 'text/html; charset=UTF-8'),
        );
    }

    public function __construct(
        private readonly string $timezone,
        private readonly string $generatedAtFormat,
        private readonly string $filenameTimestampFormat,
        private readonly string $downloadContentType,
    ) {
    }

    public function timezone(): string
    {
        return $this->timezone;
    }

    public function generatedAtFormat(): string
    {
        return $this->generatedAtFormat;
    }

    public function filenameTimestampFormat(): string
    {
        return $this->filenameTimestampFormat;
    }

    public function downloadContentType(): string
    {
        return $this->downloadContentType;
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function stringValue(array $config, string $key, string $default): string
    {
        $value = $config[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? $value : $default;
    }
}
