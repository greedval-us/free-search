<?php

declare(strict_types=1);

namespace App\MoonShine\Support;

enum AdminRole: string
{
    case Admin = 'admin';
    case Analyst = 'analyst';
    case Developer = 'developer';

    public static function fromDatabaseName(?string $name): ?self
    {
        return match (strtolower(trim((string) $name))) {
            'admin', 'administrator' => self::Admin,
            'analyst' => self::Analyst,
            'developer' => self::Developer,
            default => null,
        };
    }

    public function databaseName(): string
    {
        return ucfirst($this->value);
    }

    public function label(): string
    {
        return __("admin_panel.roles.{$this->value}");
    }
}
