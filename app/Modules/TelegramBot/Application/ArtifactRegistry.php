<?php

namespace App\Modules\TelegramBot\Application;

use App\Modules\TelegramBot\Domain\Contracts\ArtifactProvider;
use App\Modules\TelegramBot\Domain\Exceptions\ArtifactUnavailable;

final class ArtifactRegistry
{
    /** @var array<string, ArtifactProvider> */
    private array $providers = [];

    /** @param iterable<ArtifactProvider> $providers */
    public function __construct(iterable $providers)
    {
        foreach ($providers as $provider) {
            if (isset($this->providers[$provider->key()])) {
                throw new \LogicException('Duplicate bot artifact provider.');
            }
            $this->providers[$provider->key()] = $provider;
        }
    }

    public function get(string $key): ArtifactProvider
    {
        return $this->providers[$key] ?? throw new ArtifactUnavailable;
    }
}
