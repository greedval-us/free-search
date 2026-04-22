<?php

namespace App\Modules\Fio\Infrastructure\Providers;

use App\Modules\Fio\Domain\Contracts\FioPublicSearchProviderInterface;
use App\Modules\Fio\Domain\DTO\PublicSearchEntryDTO;
use RuntimeException;
use Throwable;

final class FioMultiSourceSearchProvider implements FioPublicSearchProviderInterface
{
    private const MAX_RESULTS = 80;

    public function __construct(
        private readonly FioDuckDuckGoSearchProvider $duckDuckGoProvider,
        private readonly FioBingRssSearchProvider $bingRssProvider,
    ) {
    }

    /**
     * @return array<int, PublicSearchEntryDTO>
     */
    public function search(string $fullName, ?string $qualifier = null): array
    {
        $providers = [
            $this->duckDuckGoProvider,
            $this->bingRssProvider,
        ];

        $collected = [];
        $lastError = null;

        foreach ($providers as $provider) {
            try {
                $collected = [...$collected, ...$provider->search($fullName, $qualifier)];
            } catch (Throwable $exception) {
                $lastError = $exception;
            }
        }

        $deduplicated = $this->deduplicate($collected);
        if (count($deduplicated) > 0) {
            return array_slice($deduplicated, 0, self::MAX_RESULTS);
        }

        if ($lastError instanceof Throwable) {
            throw new RuntimeException($lastError->getMessage(), (int) $lastError->getCode(), $lastError);
        }

        return [];
    }

    /**
     * @param array<int, PublicSearchEntryDTO> $entries
     * @return array<int, PublicSearchEntryDTO>
     */
    private function deduplicate(array $entries): array
    {
        $map = [];

        foreach ($entries as $entry) {
            $urlKey = mb_strtolower(trim($entry->url));
            $titleKey = mb_strtolower(trim($entry->title));
            $key = $urlKey !== '' ? $urlKey : $titleKey;

            if ($key === '' || array_key_exists($key, $map)) {
                continue;
            }

            $map[$key] = $entry;
        }

        return array_values($map);
    }
}
