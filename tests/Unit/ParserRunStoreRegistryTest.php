<?php

namespace Tests\Unit;

use App\Modules\ParserSupport\JsonRunStore;
use App\Modules\ParserSupport\ParserRunStoreRegistry;
use LogicException;
use PHPUnit\Framework\TestCase;

class ParserRunStoreRegistryTest extends TestCase
{
    public function test_it_loads_tagged_stores_once_when_the_first_module_is_requested(): void
    {
        $store = $this->store('additional-source');
        $iterations = 0;
        $taggedStores = (static function () use ($store, &$iterations): iterable {
            $iterations++;
            yield $store;
        })();
        $registry = new ParserRunStoreRegistry($taggedStores);
        $this->assertSame(0, $iterations);

        $this->assertSame($store, $registry->forModule('additional-source'));
        $this->assertSame($store, $registry->forModule('additional-source'));
        $this->assertSame(1, $iterations);
    }

    public function test_it_rejects_duplicate_module_stores(): void
    {
        $registry = new ParserRunStoreRegistry([$this->store('telegram'), $this->store('telegram')]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Duplicate parser run store [telegram].');

        $registry->forModule('telegram');
    }

    public function test_it_reports_an_unknown_module_instead_of_using_another_store(): void
    {
        $registry = new ParserRunStoreRegistry([$this->store('telegram')]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Parser run store [additional-source] is not registered.');

        $registry->forModule('additional-source');
    }

    private function store(string $module): JsonRunStore
    {
        return new class($module) extends JsonRunStore
        {
            public function __construct(private readonly string $module) {}

            protected function moduleKey(): string
            {
                return $this->module;
            }

            protected function initialState(int $userId, string $runId, array $context, string $now): array
            {
                return [];
            }
        };
    }
}
