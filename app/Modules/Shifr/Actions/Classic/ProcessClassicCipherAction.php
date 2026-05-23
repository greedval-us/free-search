<?php

namespace App\Modules\Shifr\Actions\Classic;

use App\Modules\Shifr\Actions\Classic\Contracts\ClassicCipherProcessorInterface;
use App\Modules\Shifr\Actions\Classic\Processors\KeyBasedCipherProcessor;
use App\Modules\Shifr\Actions\Classic\Processors\PatternCipherProcessor;
use App\Modules\Shifr\Actions\Classic\Processors\ShiftMirrorCipherProcessor;
use App\Modules\Shifr\DTO\Classic\ClassicCipherLookupDTO;

final class ProcessClassicCipherAction
{
    /**
     * @var array<int, ClassicCipherProcessorInterface>
     */
    private array $processors;

    public function __construct(
        ShiftMirrorCipherProcessor $shiftMirrorProcessor,
        KeyBasedCipherProcessor $keyBasedProcessor,
        PatternCipherProcessor $patternProcessor,
    ) {
        $this->processors = [
            $shiftMirrorProcessor,
            $keyBasedProcessor,
            $patternProcessor,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function execute(ClassicCipherLookupDTO $dto): ?array
    {
        foreach ($this->processors as $processor) {
            if (!$processor->supports($dto->cipher)) {
                continue;
            }

            return $processor->process($dto);
        }

        return null;
    }
}
