<?php

namespace App\Modules\ParserSupport;

use App\Modules\ParserSupport\Contracts\ParserRunBackgroundProcessorInterface;
use LogicException;

final class ParserRunBackgroundProcessorRegistry
{
    /**
     * @var array<string, ParserRunBackgroundProcessorInterface>|null
     */
    private ?array $processors = null;

    /**
     * @param  iterable<ParserRunBackgroundProcessorInterface>  $taggedProcessors
     */
    public function __construct(
        private readonly iterable $taggedProcessors,
    ) {}

    public function forModule(string $module): ParserRunBackgroundProcessorInterface
    {
        $processor = $this->processors()[$module] ?? null;

        if ($processor === null) {
            throw new LogicException("Parser background processor [{$module}] is not registered.");
        }

        return $processor;
    }

    /**
     * @return array<string, ParserRunBackgroundProcessorInterface>
     */
    private function processors(): array
    {
        if ($this->processors !== null) {
            return $this->processors;
        }

        $processors = [];

        foreach ($this->taggedProcessors as $processor) {
            $module = $processor->moduleKey();

            if (isset($processors[$module])) {
                throw new LogicException("Duplicate parser background processor [{$module}].");
            }

            $processors[$module] = $processor;
        }

        return $this->processors = $processors;
    }
}
