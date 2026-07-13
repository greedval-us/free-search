<?php

namespace App\Support\MadelineProto;

use App\Exceptions\Public\IntegrationMisconfiguredException;

final class MadelineProtoSessionPool
{
    public function __construct(private readonly MadelineProtoConfig $config) {}

    /**
     * @return list<string>
     */
    public function availableSessionNames(): array
    {
        $directory = $this->config->sessionDirectoryPath();

        if (!is_dir($directory)) {
            return [];
        }

        $matches = glob($directory . DIRECTORY_SEPARATOR . 'session*.madeline') ?: [];
        $names = [];

        foreach ($matches as $path) {
            if (!is_file($path) && !is_dir($path)) {
                continue;
            }

            $sessionName = $this->config->sessionNameFromFilePath($path);
            if ($sessionName === null) {
                continue;
            }

            $names[$sessionName] = $sessionName;
        }

        $result = array_values($names);
        usort($result, static function (string $left, string $right): int {
            if ($left === 'default') {
                return -1;
            }

            if ($right === 'default') {
                return 1;
            }

            return strcmp($left, $right);
        });

        return $result;
    }

    public function nextSessionName(): string
    {
        $sessionNames = $this->availableSessionNames();
        if ($sessionNames === []) {
            throw new IntegrationMisconfiguredException(
                'errors.api.telegram.not_configured',
                'telegram_session_not_found',
            );
        }

        if (count($sessionNames) === 1) {
            return $sessionNames[0];
        }

        $stateFilePath = $this->config->sessionPoolStateFilePath();
        $this->ensureDirectory(dirname($stateFilePath));

        $handle = fopen($stateFilePath, 'c+');
        if ($handle === false) {
            return $sessionNames[0];
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return $sessionNames[0];
            }

            rewind($handle);
            $rawState = stream_get_contents($handle);
            $decodedState = is_string($rawState) && $rawState !== ''
                ? json_decode($rawState, true)
                : null;

            $currentIndex = is_array($decodedState) && is_int($decodedState['next_index'] ?? null)
                ? $decodedState['next_index']
                : 0;

            $currentIndex %= count($sessionNames);
            $selectedSessionName = $sessionNames[$currentIndex];
            $nextIndex = ($currentIndex + 1) % count($sessionNames);

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode(['next_index' => $nextIndex], JSON_THROW_ON_ERROR));
            fflush($handle);

            return $selectedSessionName;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        mkdir($directory, 0777, true);
    }
}
