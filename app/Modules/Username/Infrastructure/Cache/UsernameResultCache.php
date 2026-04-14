<?php

namespace App\Modules\Username\Infrastructure\Cache;

use App\Modules\Username\Domain\DTO\UsernameSourceCheckResultDTO;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

final class UsernameResultCache
{
    private const CACHE_VERSION = 'v4';

    public function __construct(
        private readonly CacheRepository $cache,
    ) {
    }

    /**
     * @param callable(): array<int, UsernameSourceCheckResultDTO> $resolver
     * @return array<int, UsernameSourceCheckResultDTO>
     */
    public function rememberSearch(string $username, DateTimeInterface|int $ttl, callable $resolver): array
    {
        /** @var array<int, array<string, mixed>> $cached */
        $cached = $this->cache->remember(
            sprintf('username:search:%s:%s', self::CACHE_VERSION, $username),
            $ttl,
            fn (): array => $this->serializeItems($resolver())
        );

        return $this->hydrateItems($cached);
    }

    /**
     * @param callable(): array<int, UsernameSourceCheckResultDTO> $resolver
     * @return array<int, UsernameSourceCheckResultDTO>
     */
    public function rememberSimilarity(string $variant, DateTimeInterface|int $ttl, callable $resolver): array
    {
        /** @var array<int, array<string, mixed>> $cached */
        $cached = $this->cache->remember(
            sprintf('username:similarity:%s:%s', self::CACHE_VERSION, $variant),
            $ttl,
            fn (): array => $this->serializeItems($resolver())
        );

        return $this->hydrateItems($cached);
    }

    /**
     * @param array<int, UsernameSourceCheckResultDTO> $items
     * @return array<int, array<string, mixed>>
     */
    private function serializeItems(array $items): array
    {
        return array_map(
            static fn (UsernameSourceCheckResultDTO $item): array => $item->toArray(),
            $items
        );
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, UsernameSourceCheckResultDTO>
     */
    private function hydrateItems(array $items): array
    {
        return array_map(
            static fn (array $item): UsernameSourceCheckResultDTO => UsernameSourceCheckResultDTO::fromArray($item),
            $items
        );
    }
}
