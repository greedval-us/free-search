<?php

namespace Tests\Unit;

use App\Modules\ParserSupport\Contracts\ParserRunBackgroundProcessorInterface;
use App\Modules\ParserSupport\ParserRunBackgroundProcessorRegistry;
use LogicException;
use PHPUnit\Framework\TestCase;

class ParserRunBackgroundProcessorRegistryTest extends TestCase
{
    public function test_it_resolves_processor_by_module(): void
    {
        $processor = $this->processor('telegram');
        $registry = new ParserRunBackgroundProcessorRegistry([$processor]);

        $this->assertSame($processor, $registry->forModule('telegram'));
    }

    public function test_it_rejects_duplicate_module_processors(): void
    {
        $registry = new ParserRunBackgroundProcessorRegistry([
            $this->processor('telegram'),
            $this->processor('telegram'),
        ]);

        $this->expectException(LogicException::class);

        $registry->forModule('telegram');
    }

    private function processor(string $module): ParserRunBackgroundProcessorInterface
    {
        return new class($module) implements ParserRunBackgroundProcessorInterface
        {
            public function __construct(
                private readonly string $module,
            ) {}

            public function moduleKey(): string
            {
                return $this->module;
            }

            public function advanceRun(int $userId, string $runId): bool
            {
                return false;
            }

            public function failRun(int $userId, string $runId, string $message): void {}
        };
    }
}
