<?php

namespace App\Modules\SiteIntel\Support;

final readonly class ResolvedSiteIntelTarget
{
    public function __construct(
        public string $url,
        public string $host,
        public int $port,
        public string $ip,
    ) {}

    public function curlResolveEntry(): ?string
    {
        if (filter_var($this->host, FILTER_VALIDATE_IP) !== false) {
            return null;
        }

        $ip = filter_var($this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false
            ? '['.$this->ip.']'
            : $this->ip;

        return sprintf('%s:%d:%s', $this->host, $this->port, $ip);
    }
}
