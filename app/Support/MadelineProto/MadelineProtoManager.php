<?php

namespace App\Support\MadelineProto;

use danog\MadelineProto\API;

final class MadelineProtoManager
{
    /**
     * @var array<string, API>
     */
    private array $clients = [];

    public function __construct(
        private readonly MadelineProtoConfig $config,
        private readonly MadelineProtoClientFactory $factory,
        private readonly MadelineProtoSessionPool $sessionPool,
    ) {}

    public function client(?string $sessionName = null): API
    {
        $resolvedSessionName = $sessionName !== null
            ? $this->config->normalizeSessionName($sessionName)
            : $this->sessionPool->nextSessionName();

        if (!array_key_exists($resolvedSessionName, $this->clients)) {
            $this->clients[$resolvedSessionName] = $this->factory->make($resolvedSessionName);
        }

        return $this->clients[$resolvedSessionName];
    }

    /**
     * @return list<string>
     */
    public function availableSessionNames(): array
    {
        return $this->sessionPool->availableSessionNames();
    }
}
