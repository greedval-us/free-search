<?php

// MadelineProto's Windows performance notice is printed during Composer startup.
// Keep it in the server log so it cannot corrupt redirects, JSON or file bytes.
if (PHP_OS_FAMILY !== 'Windows') {
    return require __DIR__.'/../vendor/autoload.php';
}

$performanceNotice = 'WARNING: MadelineProto runs around 10x slower on windows due to OS and PHP limitations. Make sure to deploy MadelineProto in production only on Linux or Mac OS machines for maximum performance.';

ob_start(static function (string $output) use ($performanceNotice): string {
    if (str_contains($output, $performanceNotice.PHP_EOL)) {
        error_log($performanceNotice);
    }

    return str_replace($performanceNotice.PHP_EOL, '', $output);
});

try {
    return require __DIR__.'/../vendor/autoload.php';
} finally {
    ob_end_flush();
}
