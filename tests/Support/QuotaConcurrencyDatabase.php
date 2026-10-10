<?php

namespace Tests\Support;

use RuntimeException;

final class QuotaConcurrencyDatabase
{
    public static function configuration(): ?array
    {
        $database = getenv('QUOTA_CONCURRENCY_DB_DATABASE');
        if ($database === false || $database === '') {
            return null;
        }
        if ($database !== 'free_search_quota_concurrency_test') {
            throw new RuntimeException('Quota concurrency tests require the dedicated free_search_quota_concurrency_test database.');
        }

        $host = getenv('QUOTA_CONCURRENCY_DB_HOST');
        $username = getenv('QUOTA_CONCURRENCY_DB_USERNAME');
        $password = getenv('QUOTA_CONCURRENCY_DB_PASSWORD');
        if ($host === false || $host === '' || $username === false || $username === '' || $password === false) {
            throw new RuntimeException('Set all QUOTA_CONCURRENCY_DB credentials explicitly; application database defaults are forbidden.');
        }

        return [
            'driver' => 'mysql',
            'host' => $host,
            'port' => getenv('QUOTA_CONCURRENCY_DB_PORT') ?: '3306',
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => 'InnoDB',
        ];
    }
}
