<?php

namespace App\Modules\Fio\Infrastructure\Providers;

use App\Modules\Fio\Domain\Contracts\FioPublicSearchProviderInterface;
use App\Modules\Fio\Domain\Contracts\FioSearchDiagnosticsAwareInterface;
use App\Modules\Fio\Domain\DTO\PublicSearchEntryDTO;
use RuntimeException;
use Throwable;

final class FioMultiSourceSearchProvider implements FioPublicSearchProviderInterface, FioSearchDiagnosticsAwareInterface
{
    private const MAX_RESULTS = 260;

    /**
     * @var array{
     *     attemptedSources: array<int, array<string, mixed>>,
     *     sourceErrors: array<int, array<string, mixed>>
     * }
     */
    private array $lastDiagnostics = [
        'attemptedSources' => [],
        'sourceErrors' => [],
    ];

    public function __construct(
        private readonly FioDuckDuckGoSearchProvider $duckDuckGoProvider,
        private readonly FioBingRssSearchProvider $bingRssProvider,
        private readonly FioGoogleNewsRssSearchProvider $googleNewsProvider,
        private readonly FioRedditRssSearchProvider $redditProvider,
        private readonly FioYahooRssSearchProvider $yahooProvider,
    ) {
    }

    /**
     * @return array<int, PublicSearchEntryDTO>
     */
    public function search(string $fullName, ?string $qualifier = null): array
    {
        $this->lastDiagnostics = [
            'attemptedSources' => [],
            'sourceErrors' => [],
        ];

        $providers = [
            'duckduckgo' => $this->duckDuckGoProvider,
            'bing' => $this->bingRssProvider,
            'googlenews' => $this->googleNewsProvider,
            'reddit' => $this->redditProvider,
            'yahoo' => $this->yahooProvider,
        ];

        $collected = [];
        $lastError = null;

        foreach ($providers as $sourceKey => $provider) {
            $startedAt = microtime(true);

            try {
                $entries = $provider->search($fullName, $qualifier);
                $collected = [...$collected, ...$entries];

                $this->lastDiagnostics['attemptedSources'][] = [
                    'source' => $sourceKey,
                    'ok' => true,
                    'count' => count($entries),
                    'durationMs' => (int) round((microtime(true) - $startedAt) * 1000),
                ];
            } catch (Throwable $exception) {
                $lastError = $exception;

                $duration = (int) round((microtime(true) - $startedAt) * 1000);
                $this->lastDiagnostics['attemptedSources'][] = [
                    'source' => $sourceKey,
                    'ok' => false,
                    'count' => 0,
                    'durationMs' => $duration,
                ];
                $this->lastDiagnostics['sourceErrors'][] = [
                    'source' => $sourceKey,
                    'message' => $exception->getMessage(),
                    'durationMs' => $duration,
                ];
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

    public function diagnostics(): array
    {
        return $this->lastDiagnostics;
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
