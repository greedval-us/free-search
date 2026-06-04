<?php

namespace App\Modules\Bluesky\Support;

use App\Modules\Bluesky\Core\Contracts\BlueskyGatewayInterface;
use App\Modules\Bluesky\Presenters\BlueskyActorPresenter;

final class BlueskyActorResolver
{
    private const SEARCH_PICK_LIMIT = 10;

    public function __construct(
        private readonly BlueskyGatewayInterface $gateway,
        private readonly BlueskyActorPresenter $actorPresenter,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function resolve(string $target, bool $allowSearchFallback = true): array
    {
        $actor = ltrim(trim($target), '@');

        if ($actor === '') {
            return [];
        }

        $profiles = collect($this->gateway->getProfiles([$actor])['profiles'] ?? [])
            ->map(fn (array $item): array => $this->actorPresenter->present($item));

        if ($profiles->isNotEmpty()) {
            return $profiles->first() ?? [];
        }

        if (! $allowSearchFallback) {
            return [];
        }

        $actors = collect($this->gateway->searchActors([
            'q' => $actor,
            'limit' => self::SEARCH_PICK_LIMIT,
        ])['actors'] ?? [])
            ->map(fn (array $item): array => $this->actorPresenter->present($item));

        $needle = strtolower($actor);
        $exact = $actors->first(fn (array $item): bool => strtolower((string) ($item['handle'] ?? '')) === $needle
            || strtolower((string) ($item['did'] ?? '')) === $needle);

        return is_array($exact) ? $exact : ($actors->first() ?? []);
    }
}
