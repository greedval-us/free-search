<?php

namespace App\Modules\SiteIntel\Application\Services\DomainLite;

final class DomainLiteWhoisLookup
{
    public function __construct(
        private readonly DomainLiteWhoisClient $whoisClient,
        private readonly DomainLiteWhoisParser $whoisParser,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function lookup(string $domain): array
    {
        $whoisServer = $this->resolveWhoisServer($domain);
        if ($whoisServer === null) {
            return $this->emptyPayload(null);
        }

        $response = $this->whoisClient->query($whoisServer, $domain);
        if ($response === null || trim($response) === '') {
            return $this->emptyPayload($whoisServer);
        }

        return $this->whoisParser->parse($whoisServer, $response);
    }

    private function resolveWhoisServer(string $domain): ?string
    {
        $ianaResponse = $this->whoisClient->query('whois.iana.org', $domain);
        if ($ianaResponse === null) {
            return null;
        }

        if (preg_match('/^refer:\s*(.+)$/im', $ianaResponse, $matches) === 1) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPayload(?string $server): array
    {
        return [
            'server' => $server,
            'available' => false,
            'createdAt' => null,
            'updatedAt' => null,
            'expiresAt' => null,
            'registrar' => null,
            'country' => null,
            'sample' => null,
        ];
    }
}

