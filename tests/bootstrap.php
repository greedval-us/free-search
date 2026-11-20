<?php

require dirname(__DIR__).'/vendor/autoload.php';

// RefreshDatabase runs migrate:fresh. Pin its target before Laravel can load the application's .env.
$settings = [
    'APP_ENV' => 'testing',
    'APP_CONFIG_CACHE' => 'bootstrap/cache/phpunit-config.php',
    'DB_CONNECTION' => 'mysql',
    'DB_DATABASE' => 'free_search_test',
    'DB_URL' => '',
    'DB_SOCKET' => '',
];

if (is_file(dirname(__DIR__).'/'.$settings['APP_CONFIG_CACHE'])) {
    throw new RuntimeException('Remove the cached PHPUnit configuration before running database tests.');
}

foreach (['HOST' => '127.0.0.1', 'PORT' => '3306', 'USERNAME' => 'free_search_test', 'PASSWORD' => ''] as $key => $default) {
    $value = getenv('TEST_DB_'.$key);
    $settings['DB_'.$key] = $value === false ? $default : $value;
}

foreach ($settings as $name => $value) {
    putenv($name.'='.$value);
    $_ENV[$name] = $_SERVER[$name] = $value;
}
