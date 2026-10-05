<?php

namespace App\Support\Http;

final class DocumentResponseHeaders
{
    /**
     * @return array<string, string>
     */
    public static function download(): array
    {
        return [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "sandbox; default-src 'none'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'",
            'Referrer-Policy' => 'no-referrer',
            'X-Frame-Options' => 'DENY',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function report(): array
    {
        return array_replace(self::download(), [
            'Content-Security-Policy' => "sandbox; default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'",
        ]);
    }
}
