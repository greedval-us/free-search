<?php

use App\Models\User;
use App\Modules\YouTube\Analytics\Reports\AnalyticsReportException;
use App\Modules\YouTube\Analytics\Reports\AnalyticsReportScheduleService;
use App\Services\Access\Contracts\FeatureUsageCounterInterface;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Support\Facades\Http;
use Tests\Support\QuotaConcurrencyDatabase;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$configuration = QuotaConcurrencyDatabase::configuration();
if ($configuration === null) {
    throw new RuntimeException('The dedicated concurrency database is not configured.');
}
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->loadEnvironmentFrom('.env.quota-concurrency-test-does-not-exist');
$app->afterBootstrapping(LoadConfiguration::class, function ($app) use ($configuration): void {
    $app['config']->set('database.default', 'quota_concurrency');
    $app['config']->set('database.connections.quota_concurrency', $configuration);
    $app['config']->set('app.timezone', 'UTC');
    $app['config']->set('cache.default', 'array');
    $app['config']->set('youtube_analytics_reports.queue.connection', 'database');
    $app['config']->set('youtube_analytics_reports.max_schedules', 2);
    $app['config']->set('queue.connections.database.retry_after', 1200);
});
$app->make(Kernel::class)->bootstrap();
Http::preventStrayRequests();
Carbon::setTestNow($argv[6]);
CarbonImmutable::setTestNow($argv[6]);
file_put_contents($argv[4].'.ready.'.$argv[5], 'ready');
$deadline = microtime(true) + 15;
while (! is_file($argv[4])) {
    if (microtime(true) >= $deadline) {
        throw new RuntimeException('Concurrency test barrier timed out.');
    }
    usleep(1000);
}

$counter = $app->make(FeatureUsageCounterInterface::class);
$user = User::query()->findOrFail((int) $argv[2]);
if ($argv[1] === 'schedule') {
    try {
        $app->make(AnalyticsReportScheduleService::class)->create($user, [
            'name' => 'Concurrency '.$argv[5], 'channels' => ['@example'],
            'interval' => '1', 'send_time' => '09:00', 'timezone' => 'UTC',
        ]);
        $result = 'created';
    } catch (AnalyticsReportException $exception) {
        $result = $exception->reason;
    }
} elseif ($argv[1] === 'consume') {
    $receipt = $counter->consume($user, 'telegram.analytics', 2);
    $result = $receipt?->id;
} else {
    $counter->release($user, $argv[3]);
    $result = null;
}
echo 'QUOTA_RESULT:'.json_encode($result, JSON_THROW_ON_ERROR).PHP_EOL;
