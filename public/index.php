<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
if (PHP_OS_FAMILY === 'Windows') {
    // MadelineProto's Composer polyfill prints a platform warning before HTTP headers.
    // Keep dependency bootstrap output in the server log instead of corrupting JSON responses.
    ob_start();
    try {
        require __DIR__.'/../vendor/autoload.php';
    } finally {
        $autoloadOutput = ob_get_clean();
        if ($autoloadOutput !== false && $autoloadOutput !== '') {
            error_log(trim($autoloadOutput));
        }
    }
} else {
    require __DIR__.'/../vendor/autoload.php';
}

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
