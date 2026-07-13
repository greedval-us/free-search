<?php

namespace App\Support\MadelineProto;

use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Settings\Logger as LoggerSettings;

final class MadelineProtoClientFactory
{
    public function __construct(private readonly MadelineProtoConfig $config) {}

    public function make(string $sessionName = 'default'): API
    {
        $normalizedSessionName = $this->config->normalizeSessionName($sessionName);
        $sessionPath = $this->config->sessionFilePathFor($normalizedSessionName);
        $logPath = $this->config->logFilePathFor($normalizedSessionName);

        $this->ensureDirectory(dirname($sessionPath));
        $this->ensureDirectory(dirname($logPath));

        $settings = (new Settings)
            ->setAppInfo(
                (new AppInfo)
                    ->setApiId($this->config->apiId())
                    ->setApiHash($this->config->apiHash())
            )
            ->setLogger(
                (new LoggerSettings)
                    ->setType(Logger::FILE_LOGGER)
                    ->setExtra($logPath)
            );

        return new API($sessionPath, $settings);
    }

    private function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        mkdir($directory, 0777, true);
    }
}
