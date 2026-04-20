<?php

namespace App\Modules\SiteIntel\Application\Services;

use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class SiteHealthService
{
    private const MAX_REDIRECTS = 5;

    /**
     * @return array<string, mixed>
     */
    public function check(string $url): array
    {
        $host = (string) parse_url($url, PHP_URL_HOST);
        if ($host === '') {
            throw new RuntimeException('Invalid target host.');
        }

        $dns = $this->resolveDns($host);
        $http = $this->collectHttpChain($url);
        $finalHeaders = $http['finalHeaders'];
        $ssl = $this->inspectSsl(
            (string) parse_url((string) $http['finalUrl'], PHP_URL_HOST),
            str_starts_with((string) $http['finalUrl'], 'https://')
        );
        $securityHeaders = $this->securityHeaders($finalHeaders);
        $score = $this->calculateScore($dns, $http, $ssl, $securityHeaders);

        return [
            'target' => [
                'input' => $url,
                'host' => $host,
            ],
            'checkedAt' => Carbon::now()->toIso8601String(),
            'dns' => $dns,
            'http' => [
                'chain' => $http['chain'],
                'finalUrl' => $http['finalUrl'],
                'finalStatus' => $http['finalStatus'],
                'totalRedirects' => $http['totalRedirects'],
            ],
            'headers' => $securityHeaders,
            'ssl' => $ssl,
            'score' => $score,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveDns(string $host): array
    {
        $a = gethostbynamel($host) ?: [];
        $aaaaRaw = dns_get_record($host, DNS_AAAA);
        $aaaa = [];

        if (is_array($aaaaRaw)) {
            foreach ($aaaaRaw as $record) {
                if (isset($record['ipv6'])) {
                    $aaaa[] = (string) $record['ipv6'];
                }
            }
        }

        return [
            'a' => array_values(array_unique($a)),
            'aaaa' => array_values(array_unique($aaaa)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectHttpChain(string $url): array
    {
        $chain = [];
        $currentUrl = $url;
        $finalHeaders = [];
        $finalStatus = 0;

        for ($step = 0; $step <= self::MAX_REDIRECTS; $step++) {
            $startedAt = microtime(true);

            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'FreeSearch-SiteHealth/1.0',
                    'Accept' => '*/*',
                ])
                    ->withOptions([
                        'allow_redirects' => false,
                        'verify' => false,
                    ])
                    ->timeout(10)
                    ->get($currentUrl);
            } catch (ConnectionException $exception) {
                $chain[] = [
                    'url' => $currentUrl,
                    'status' => 0,
                    'location' => null,
                    'responseTimeMs' => (int) round((microtime(true) - $startedAt) * 1000),
                    'error' => $exception->getMessage(),
                ];

                return [
                    'chain' => $chain,
                    'finalUrl' => $currentUrl,
                    'finalStatus' => 0,
                    'totalRedirects' => max(0, count($chain) - 1),
                    'finalHeaders' => [],
                ];
            }

            $status = $response->status();
            $location = $response->header('Location');
            $responseTimeMs = (int) round((microtime(true) - $startedAt) * 1000);
            $headers = $response->headers();

            $chain[] = [
                'url' => $currentUrl,
                'status' => $status,
                'location' => $location,
                'responseTimeMs' => $responseTimeMs,
                'error' => null,
            ];

            $finalHeaders = is_array($headers) ? $headers : [];
            $finalStatus = $status;

            if (!$this->isRedirectStatus($status) || !is_string($location) || $location === '') {
                break;
            }

            $resolved = $this->resolveRedirectUrl($currentUrl, $location);
            if ($resolved === null) {
                break;
            }

            $currentUrl = $resolved;
        }

        return [
            'chain' => $chain,
            'finalUrl' => $currentUrl,
            'finalStatus' => $finalStatus,
            'totalRedirects' => max(0, count($chain) - 1),
            'finalHeaders' => $finalHeaders,
        ];
    }

    private function isRedirectStatus(int $status): bool
    {
        return in_array($status, [301, 302, 303, 307, 308], true);
    }

    private function resolveRedirectUrl(string $currentUrl, string $location): ?string
    {
        if (str_starts_with($location, 'http://') || str_starts_with($location, 'https://')) {
            return $location;
        }

        $parts = parse_url($currentUrl);
        if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $scheme = (string) $parts['scheme'];
        $host = (string) $parts['host'];
        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';

        if (str_starts_with($location, '//')) {
            return $scheme . ':' . $location;
        }

        if (str_starts_with($location, '/')) {
            return sprintf('%s://%s%s%s', $scheme, $host, $port, $location);
        }

        $path = (string) ($parts['path'] ?? '/');
        $basePath = str_ends_with($path, '/') ? $path : dirname($path) . '/';
        if ($basePath === './') {
            $basePath = '/';
        }

        return sprintf('%s://%s%s%s%s', $scheme, $host, $port, $basePath, $location);
    }

    /**
     * @param array<string, mixed> $headers
     * @return array<string, mixed>
     */
    private function inspectSsl(string $host, bool $isHttps): array
    {
        if ($host === '' || !$isHttps) {
            return [
                'available' => false,
                'subject' => null,
                'issuer' => null,
                'validFrom' => null,
                'validTo' => null,
                'daysRemaining' => null,
            ];
        }

        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $client = @stream_socket_client(
            'ssl://' . $host . ':443',
            $errno,
            $errorString,
            8,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($client === false) {
            return [
                'available' => false,
                'subject' => null,
                'issuer' => null,
                'validFrom' => null,
                'validTo' => null,
                'daysRemaining' => null,
            ];
        }

        $params = stream_context_get_params($client);
        $certificate = $params['options']['ssl']['peer_certificate'] ?? null;
        if (!is_resource($certificate) && !is_object($certificate)) {
            return [
                'available' => false,
                'subject' => null,
                'issuer' => null,
                'validFrom' => null,
                'validTo' => null,
                'daysRemaining' => null,
            ];
        }

        $parsed = openssl_x509_parse($certificate);
        if (!is_array($parsed)) {
            return [
                'available' => false,
                'subject' => null,
                'issuer' => null,
                'validFrom' => null,
                'validTo' => null,
                'daysRemaining' => null,
            ];
        }

        $validFromTs = isset($parsed['validFrom_time_t']) ? (int) $parsed['validFrom_time_t'] : null;
        $validToTs = isset($parsed['validTo_time_t']) ? (int) $parsed['validTo_time_t'] : null;
        $daysRemaining = $validToTs !== null ? (int) floor(($validToTs - time()) / 86400) : null;

        return [
            'available' => true,
            'subject' => $parsed['subject']['CN'] ?? null,
            'issuer' => $parsed['issuer']['O'] ?? ($parsed['issuer']['CN'] ?? null),
            'validFrom' => $validFromTs ? Carbon::createFromTimestamp($validFromTs)->toIso8601String() : null,
            'validTo' => $validToTs ? Carbon::createFromTimestamp($validToTs)->toIso8601String() : null,
            'daysRemaining' => $daysRemaining,
        ];
    }

    /**
     * @param array<string, mixed> $headers
     * @return array<string, array<string, mixed>>
     */
    private function securityHeaders(array $headers): array
    {
        $required = [
            'strict-transport-security',
            'content-security-policy',
            'x-frame-options',
            'x-content-type-options',
            'referrer-policy',
            'permissions-policy',
        ];

        $normalized = [];
        foreach ($headers as $key => $value) {
            $normalized[strtolower((string) $key)] = $value;
        }

        $result = [];

        foreach ($required as $headerName) {
            $rawValue = $normalized[$headerName] ?? null;
            $value = null;

            if (is_array($rawValue)) {
                $value = count($rawValue) > 0 ? (string) $rawValue[0] : null;
            } elseif (is_string($rawValue)) {
                $value = $rawValue;
            }

            $result[$headerName] = [
                'present' => $value !== null && trim($value) !== '',
                'value' => $value,
            ];
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $dns
     * @param array<string, mixed> $http
     * @param array<string, mixed> $ssl
     * @param array<string, array<string, mixed>> $securityHeaders
     * @return array<string, mixed>
     */
    private function calculateScore(array $dns, array $http, array $ssl, array $securityHeaders): array
    {
        $score = 100;
        $signals = [];

        if (empty($dns['a']) && empty($dns['aaaa'])) {
            $score -= 25;
            $signals[] = 'no_dns_records';
        }

        $finalStatus = (int) ($http['finalStatus'] ?? 0);
        if ($finalStatus === 0) {
            $score -= 35;
            $signals[] = 'unreachable';
        } elseif ($finalStatus >= 400) {
            $score -= 25;
            $signals[] = 'http_error_status';
        }

        if (str_starts_with((string) ($http['finalUrl'] ?? ''), 'http://')) {
            $score -= 20;
            $signals[] = 'final_url_not_https';
        }

        $totalRedirects = (int) ($http['totalRedirects'] ?? 0);
        if ($totalRedirects > 3) {
            $score -= 10;
            $signals[] = 'too_many_redirects';
        }

        if (($ssl['available'] ?? false) !== true && str_starts_with((string) ($http['finalUrl'] ?? ''), 'https://')) {
            $score -= 20;
            $signals[] = 'ssl_unavailable';
        }

        $daysRemaining = $ssl['daysRemaining'] ?? null;
        if (is_int($daysRemaining)) {
            if ($daysRemaining < 0) {
                $score -= 35;
                $signals[] = 'ssl_expired';
            } elseif ($daysRemaining <= 30) {
                $score -= 15;
                $signals[] = 'ssl_expiring_soon';
            }
        }

        foreach ($securityHeaders as $headerName => $headerInfo) {
            if (($headerInfo['present'] ?? false) !== true) {
                $score -= 6;
                $signals[] = 'missing_' . str_replace('-', '_', $headerName);
            }
        }

        $score = max(0, min(100, $score));
        $level = match (true) {
            $score >= 80 => 'high',
            $score >= 55 => 'medium',
            default => 'low',
        };

        return [
            'value' => $score,
            'level' => $level,
            'signals' => array_values(array_unique($signals)),
        ];
    }
}

