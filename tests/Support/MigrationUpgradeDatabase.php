<?php

namespace Tests\Support;

use RuntimeException;

final class MigrationUpgradeDatabase
{
    public static function configuration(): ?array
    {
        $database = getenv('MIGRATION_UPGRADE_DB_DATABASE');
        if ($database === false || $database === '') {
            return null;
        }
        if ($database !== 'free_search_refactor_upgrade_test') {
            throw new RuntimeException('Migration upgrade tests require the dedicated free_search_refactor_upgrade_test database.');
        }
        $host = getenv('MIGRATION_UPGRADE_DB_HOST');
        $username = getenv('MIGRATION_UPGRADE_DB_USERNAME');
        $password = getenv('MIGRATION_UPGRADE_DB_PASSWORD');
        if ($host === false || $host === '' || $username === false || $username === '' || $password === false) {
            throw new RuntimeException('Set every MIGRATION_UPGRADE_DB credential explicitly; application database defaults are forbidden.');
        }

        return [
            'driver' => 'mysql', 'host' => $host,
            'port' => getenv('MIGRATION_UPGRADE_DB_PORT') ?: '3306',
            'database' => $database, 'username' => $username, 'password' => $password,
            'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '', 'strict' => true, 'engine' => 'InnoDB',
        ];
    }
}
