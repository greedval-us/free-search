<?php

namespace Tests\Unit;

use App\Support\MadelineProto\MadelineProtoConfig;
use App\Support\MadelineProto\MadelineProtoSessionPool;
use Tests\TestCase;

class MadelineProtoSessionPoolTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $createdPaths = [];

    public function test_it_discovers_default_and_named_sessions(): void
    {
        $config = $this->makeConfig('madeline-discovery');
        $this->touchDirectory($config->sessionFilePathFor('default'));
        $this->touchFile($config->sessionFilePathFor('worker-b'));
        $this->touchFile($config->sessionFilePathFor('worker-a'));

        $pool = new MadelineProtoSessionPool($config);

        $this->assertSame(
            ['default', 'worker-a', 'worker-b'],
            $pool->availableSessionNames(),
        );
    }

    public function test_it_rotates_sessions_in_round_robin_order(): void
    {
        $config = $this->makeConfig('madeline-round-robin');
        $this->touchFile($config->sessionFilePathFor('default'));
        $this->touchFile($config->sessionFilePathFor('worker-a'));
        $this->touchFile($config->sessionFilePathFor('worker-b'));
        @unlink($config->sessionPoolStateFilePath());

        $pool = new MadelineProtoSessionPool($config);

        $this->assertSame('default', $pool->nextSessionName());
        $this->assertSame('worker-a', $pool->nextSessionName());
        $this->assertSame('worker-b', $pool->nextSessionName());
        $this->assertSame('default', $pool->nextSessionName());
    }

    private function makeConfig(string $suffix): MadelineProtoConfig
    {
        $uniqueSuffix = $suffix . '-' . bin2hex(random_bytes(4));

        return MadelineProtoConfig::fromArray([
            'api_id' => 1,
            'api_hash' => 'hash',
            'session_path' => 'framework/testing/' . $uniqueSuffix . '/sessions',
            'log_path' => 'logs/' . $uniqueSuffix . '.log',
        ]);
    }

    private function touchFile(string $path): void
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if (!is_file($path)) {
            file_put_contents($path, 'session');
        }

        $this->createdPaths[] = dirname($path, 2);
    }

    private function touchDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $this->createdPaths[] = dirname($path, 2);
    }

    protected function tearDown(): void
    {
        foreach (array_unique(array_reverse($this->createdPaths)) as $path) {
            $this->deleteDirectory($path);
        }

        $this->createdPaths = [];

        parent::tearDown();
    }

    private function deleteDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path . DIRECTORY_SEPARATOR . $item;

            if (is_dir($fullPath)) {
                $this->deleteDirectory($fullPath);
                continue;
            }

            @unlink($fullPath);
        }

        @rmdir($path);
    }
}
