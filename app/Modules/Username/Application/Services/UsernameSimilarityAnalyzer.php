<?php

namespace App\Modules\Username\Application\Services;

use App\Modules\Username\Application\Support\UsernameConfig;
use App\Modules\Username\Domain\Contracts\UsernameSourceCheckerInterface;
use App\Modules\Username\Domain\DTO\UsernameSourceDTO;
use App\Modules\Username\Domain\Services\UsernameSimilarityVariantGenerator;
use App\Modules\Username\Infrastructure\Cache\UsernameResultCache;

final class UsernameSimilarityAnalyzer
{
    public function __construct(
        private readonly UsernameSourceCheckerInterface $sourceChecker,
        private readonly UsernameResultCache $resultCache,
        private readonly UsernameSimilarityVariantGenerator $variantGenerator,
        private readonly UsernameConfig $config,
    ) {
    }

    /**
     * @param array<int, UsernameSourceDTO> $sources
     * @return array<string, mixed>
     */
    public function analyze(string $username, array $sources): array
    {
        $variants = $this->variantGenerator->generate($username);

        if ($variants === []) {
            return ['variants' => []];
        }

        $priorityKeys = $this->config->similarityPrioritySourceKeys();
        $maxDeepCheck = $this->config->similarityDeepCheckVariants();
        $ttl = $this->config->similarityCacheTtlSeconds();

        $prioritySources = array_values(array_filter(
            $sources,
            static fn (UsernameSourceDTO $source): bool => in_array($source->key, $priorityKeys, true)
        ));

        $out = [];

        foreach ($variants as $index => $variantMeta) {
            $variant = $variantMeta['username'];
            $found = null;
            $checked = null;

            if ($index < $maxDeepCheck && $prioritySources !== []) {
                $checks = $this->resultCache->rememberSimilarity(
                    $variant,
                    now()->addSeconds($ttl),
                    fn (): array => $this->sourceChecker->checkMany($prioritySources, $variant)
                );

                $found = 0;
                $checked = count($checks);

                foreach ($checks as $check) {
                    if ($check->status->value === 'found') {
                        $found++;
                    }
                }
            }

            $out[] = [
                'username' => $variant,
                'reason' => $variantMeta['reason'],
                'foundInPrioritySources' => $found,
                'checkedPrioritySources' => $checked,
            ];
        }

        return ['variants' => $out];
    }
}
