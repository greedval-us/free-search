<?php

namespace App\Modules\ParserSupport;

use LogicException;

final class ParserRunStoreRegistry
{
    /** @var array<string, JsonRunStore>|null */
    private ?array $stores = null;

    /** @param iterable<JsonRunStore> $taggedStores */
    public function __construct(private readonly iterable $taggedStores) {}

    public function forModule(string $module): JsonRunStore
    {
        return $this->stores()[$module] ?? throw new LogicException("Parser run store [{$module}] is not registered.");
    }

    /** @return array<string, JsonRunStore> */
    private function stores(): array
    {
        if ($this->stores !== null) {
            return $this->stores;
        }

        $stores = [];
        foreach ($this->taggedStores as $store) {
            $module = $store->module();
            if (isset($stores[$module])) {
                throw new LogicException("Duplicate parser run store [{$module}].");
            }
            $stores[$module] = $store;
        }

        return $this->stores = $stores;
    }
}
