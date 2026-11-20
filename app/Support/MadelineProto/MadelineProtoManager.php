<?php

namespace App\Support\MadelineProto;

use App\Exceptions\Public\IntegrationMisconfiguredException;
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

        if (! array_key_exists($resolvedSessionName, $this->clients)) {
            $this->clients[$resolvedSessionName] = $this->factory->make($resolvedSessionName);
        }

        return $this->clients[$resolvedSessionName];
    }

    public function authorizedClient(): API
    {
        $client = $this->client();
        if ($client->getAuthorization() !== API::LOGGED_IN) {
            throw new IntegrationMisconfiguredException('errors.api.telegram.session_unauthorized', 'telegram_session_unauthorized');
        }

        return $client;
    }

    /**
     * @return list<string>
     */
    public function availableSessionNames(): array
    {
        return $this->sessionPool->availableSessionNames();
    }
}
