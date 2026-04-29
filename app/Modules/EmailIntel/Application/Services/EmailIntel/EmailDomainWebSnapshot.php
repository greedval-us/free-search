<?php

namespace App\Modules\EmailIntel\Application\Services\EmailIntel;

final class EmailDomainWebSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public function inspect(string $domain): array
    {
        $url = 'https://' . $domain . '/';
        $startedAt = microtime(true);
        $headers = @get_headers($url, true);
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        if (!is_array($headers)) {
            return [
                'url' => $url,
                'available' => false,
                'status' => null,
                'durationMs' => $durationMs,
                'securityHeaders' => [],
            ];
        }

        $statusLine = is_string($headers[0] ?? null) ? $headers[0] : '';
        preg_match('/\s(\d{3})\s/', $statusLine, $matches);
        $status = isset($matches[1]) ? (int) $matches[1] : null;

        return [
            'url' => $url,
            'available' => $status !== null && $status < 500,
            'status' => $status,
            'durationMs' => $durationMs,
            'securityHeaders' => [
                'strictTransportSecurity' => array_key_exists('Strict-Transport-Security', $headers),
                'contentSecurityPolicy' => array_key_exists('Content-Security-Policy', $headers),
                'xFrameOptions' => array_key_exists('X-Frame-Options', $headers),
                'xContentTypeOptions' => array_key_exists('X-Content-Type-Options', $headers),
            ],
        ];
    }
}
