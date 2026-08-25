<?php

namespace App\Support\Security;

final class ContentSecurityPolicy
{
    public function build(string $nonce): string
    {
        /** @var array<string, list<string>> $directives */
        $directives = config('security.content_security_policy.directives', []);
        $directives['script-src'][] = "'nonce-{$nonce}'";

        return collect($directives)
            ->map(static function (array $sources, string $directive): string {
                $value = implode(' ', array_unique($sources));

                return trim("{$directive} {$value}");
            })
            ->implode('; ');
    }
}
