<?php

namespace App\Support\MadelineProto;

use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Settings\Logger as LoggerSettings;

final class MadelineProtoClientFactory
{
    public function __construct(
        private readonly MadelineProtoConfig $config,
        private readonly MadelineProtoRuntime $runtime,
    ) {}

    public function make(string $sessionName = 'default'): API
    {
        return $this->create($sessionName, false);
    }

    public function makeForAuthentication(string $sessionName): API
    {
        return $this->create($sessionName, true);
    }

    private function create(string $sessionName, bool $authentication): API
    {
        $normalizedSessionName = $this->config->normalizeSessionName($sessionName);
        $sessionPath = $this->config->sessionFilePathFor($normalizedSessionName);
        $logPath = $this->config->logFilePathFor($normalizedSessionName);

        $this->ensureDirectory(dirname($sessionPath), $authentication ? 0750 : 0777);
        $this->ensureDirectory(dirname($logPath), $authentication ? 0750 : 0777);

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
                    ->setLevel($authentication ? Logger::NOTICE : Logger::VERBOSE)
            );

        if ($authentication) {
            $settings->getAppInfo()->setShowPrompt(false);
            $settings->getRpc()->setRpcDropTimeout((int) config('madelineproto.admin_auth.rpc_timeout_seconds'));
            $settings->getRpc()->setFloodTimeout((int) config('madelineproto.admin_auth.flood_timeout_seconds'));
            $settings->getConnection()->setTimeout((float) config('madelineproto.admin_auth.connection_timeout_seconds'));
        }

        $mask = $authentication ? umask(0077) : null;
        try {
            return $this->runtime->executeFrom(
                dirname($logPath),
                static fn (): API => new API($sessionPath, $settings),
            );
        } finally {
            if ($mask !== null) {
                umask($mask);
            }
        }
    }

    private function ensureDirectory(string $directory, int $mode): void
    {
        if (is_dir($directory)) {
            return;
        }

        mkdir($directory, $mode, true);
    }
}
