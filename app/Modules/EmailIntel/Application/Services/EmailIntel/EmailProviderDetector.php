<?php

namespace App\Modules\EmailIntel\Application\Services\EmailIntel;

final class EmailProviderDetector
{
    /**
     * @param array<int, array{host: string, priority: int}> $mx
     * @return array{name: string, type: string, confidence: int, mxHosts: array<int, string>}
     */
    public function detect(string $domain, array $mx): array
    {
        $hosts = array_map(static fn (array $record): string => mb_strtolower($record['host']), $mx);
        $joinedHosts = implode(' ', $hosts);

        $provider = match (true) {
            str_contains($joinedHosts, 'google.com') || str_contains($joinedHosts, 'googlemail.com') => ['Google Workspace/Gmail', 'managed', 95],
            str_contains($joinedHosts, 'protection.outlook.com') || str_contains($joinedHosts, 'outlook.com') => ['Microsoft 365/Outlook', 'managed', 95],
            str_contains($joinedHosts, 'yandex.net') || str_contains($domain, 'yandex.') => ['Yandex Mail', 'managed', 90],
            str_contains($joinedHosts, 'mail.ru') => ['Mail.ru', 'managed', 90],
            str_contains($joinedHosts, 'protonmail') || str_contains($joinedHosts, 'proton.ch') => ['Proton Mail', 'managed', 90],
            str_contains($joinedHosts, 'zoho') => ['Zoho Mail', 'managed', 90],
            str_contains($joinedHosts, 'fastmail') => ['Fastmail', 'managed', 90],
            $hosts !== [] => ['Custom/unknown mail server', 'custom', 55],
            default => ['Unknown', 'unknown', 0],
        };

        return [
            'name' => $provider[0],
            'type' => $provider[1],
            'confidence' => $provider[2],
            'mxHosts' => $hosts,
        ];
    }
}
