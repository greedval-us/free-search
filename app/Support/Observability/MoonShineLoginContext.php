<?php

declare(strict_types=1);

namespace App\Support\Observability;

final readonly class MoonShineLoginContext
{
    public function __construct(
        public string $adminId,
        public string $adminEmail,
        public string $ip,
        public string $userAgent,
        public string $guard,
        public string $timestampIso,
    ) {
    }
}
