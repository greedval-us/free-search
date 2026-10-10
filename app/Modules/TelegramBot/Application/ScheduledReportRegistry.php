<?php

namespace App\Modules\TelegramBot\Application;

use App\Modules\TelegramBot\Domain\Contracts\ScheduledReportArtifactProvider;

final class ScheduledReportRegistry
{
    /** @var array<string, ScheduledReportArtifactProvider> */
    private array $providers = [];

    /** @param iterable<ScheduledReportArtifactProvider> $providers */
    public function __construct(iterable $providers)
    {
        foreach ($providers as $provider) {
            if (isset($this->providers[$provider->key()])) {
                throw new \LogicException('Duplicate scheduled report provider.');
            }
            $this->providers[$provider->key()] = $provider;
        }
    }

    public function find(string $key): ?ScheduledReportArtifactProvider
    {
        return $this->providers[$key] ?? null;
    }
}
