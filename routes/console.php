<?php

use App\Console\Commands\CleanupParserRunFiles;
use App\Console\Commands\SendSubscriptionExpiryNotifications;
use App\Models\RequestLog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(SendSubscriptionExpiryNotifications::class)->dailyAt('09:00');
Schedule::command(CleanupParserRunFiles::class)->dailyAt(
    (string) config('osint.parser_runs.cleanup_schedule', '03:30')
);
Schedule::command('model:prune', [
    '--model' => [RequestLog::class],
])->dailyAt((string) config('activity.cleanup_schedule', '04:00'));
