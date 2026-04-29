<?php

namespace App\Modules\EmailIntel\Application\Services\EmailIntel;

final class EmailSearchLinkBuilder
{
    /**
     * @return array<int, array{label: string, type: string, url: string}>
     */
    public function build(string $email, string $localPart, string $domain): array
    {
        return [
            ['label' => 'Google exact email', 'type' => 'email', 'url' => 'https://www.google.com/search?q=' . rawurlencode('"' . $email . '"')],
            ['label' => 'Google domain', 'type' => 'domain', 'url' => 'https://www.google.com/search?q=' . rawurlencode('site:' . $domain)],
            ['label' => 'Bing exact email', 'type' => 'email', 'url' => 'https://www.bing.com/search?q=' . rawurlencode('"' . $email . '"')],
            ['label' => 'GitHub exact email', 'type' => 'code', 'url' => 'https://github.com/search?q=' . rawurlencode('"' . $email . '"') . '&type=code'],
            ['label' => 'GitHub local part', 'type' => 'username', 'url' => 'https://github.com/search?q=' . rawurlencode($localPart) . '&type=users'],
            ['label' => 'Reddit search', 'type' => 'mentions', 'url' => 'https://www.reddit.com/search/?q=' . rawurlencode('"' . $email . '"')],
        ];
    }
}
