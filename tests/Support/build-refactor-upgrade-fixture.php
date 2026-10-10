<?php

/**
 * Regeneration only: php tests/Support/build-refactor-upgrade-fixture.php
 * Requires Git history containing baseline 415d288. Do not invoke from CI/test
 * setup: MigrationUpgradeTest reads the committed JSON directly, without Git.
 * Loads only legacy class definitions plus vendor autoload, never the Laravel
 * application, environment file, database, source clients or queue workers.
 */

use App\Jobs\ProcessParserRun;
use App\Modules\SiteIntel\Application\Reports\Jobs\GenerateSiteIntelReport;
use App\Modules\YouTube\Parser\YouTubeParserRunStore;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\DateFactory;
use Illuminate\Support\Facades\Facade;
use Symfony\Component\Process\Process;

require dirname(__DIR__, 2).'/vendor/autoload.php';

date_default_timezone_set('UTC');
$root = dirname(__DIR__, 2);
$baseline = '415d288ed13f7b8dee9b96dc98c798e3cee16116';
$read = static function (array $arguments) use ($root): string {
    $process = new Process(['git', ...$arguments], $root);
    $process->mustRun();

    return str_replace("\r\n", "\n", $process->getOutput());
};
$fixture = ['baseline' => $baseline, 'migrations' => [], 'legacy_source_sha256' => [], 'jobs' => []];
foreach (explode("\n", trim($read(['ls-tree', '-r', '--name-only', $baseline, '--', 'database/migrations']))) as $path) {
    $fixture['migrations'][$path] = hash('sha256', $read(['show', $baseline.':'.$path]));
}
$container = new Container;
Container::setInstance($container);
Facade::setFacadeApplication($container);
$container->instance('date', new DateFactory);
$config = [];
foreach (['bluesky', 'mastodon', 'youtube', 'telegram', 'site_intel'] as $module) {
    $config[$module.($module === 'site_intel' ? '_reports' : '_analytics_reports')] = ['queue' => ['timeout' => 120, 'connection' => 'database', 'name' => 'legacy-upgrade']];
}
$container->instance('config', new Repository($config));
Carbon::setTestNow('2026-10-10 12:00:00 UTC');
CarbonImmutable::setTestNow('2026-10-10 12:00:00 UTC');
$paths = [
    'app/Jobs/ProcessParserRun.php',
    'app/Modules/ParserSupport/JsonRunStore.php',
    'app/Modules/YouTube/Parser/YouTubeParserRunStore.php',
];
foreach (['Bluesky', 'Mastodon', 'YouTube', 'Telegram'] as $module) {
    $paths[] = 'app/Modules/'.$module.'/Analytics/Reports/Jobs/GenerateAnalyticsReport.php';
}
$paths[] = 'app/Modules/SiteIntel/Application/Reports/Jobs/GenerateSiteIntelReport.php';
$temporaryFiles = [];
try {
    foreach ($paths as $path) {
        $source = $read(['show', $baseline.':'.$path]);
        $fixture['legacy_source_sha256'][$path] = hash('sha256', $source);
        $temporaryFile = tempnam(sys_get_temp_dir(), 'upgrade-baseline-');
        $temporaryFiles[] = $temporaryFile;
        file_put_contents($temporaryFile, $source);
        require $temporaryFile;
    }
    $runId = '11111111-1111-4111-8111-111111111111';
    $store = (new ReflectionClass(YouTubeParserRunStore::class))->newInstanceWithoutConstructor();
    $run = (new ReflectionMethod($store, 'initialState'))->invoke($store, 11, $runId, ['videoId' => 'legacy-video'], now()->toIso8601String());
    $run['progress'] = 25;
    $run['cursor']['checkpointVersion'] = 3;
    $run['cursor']['commentsPageToken'] = 'legacy-page-token';
    $run['cursor']['commentsPage'] = 1;
    $run['stats']['processedComments'] = 1;
    $run['data']['commentIds'] = ['legacy-comment'];
    $run['data']['commentsIndex'] = ['legacy-comment' => ['id' => 'legacy-comment', 'text' => 'Сохранённый комментарий']];
    $fixture['parser_checkpoint'] = json_encode($run, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $fixture['jobs']['parser'] = serialize(new ProcessParserRun('youtube', 11, $runId, 3, $run['cursor']['stepRetryUntil']));
    $token = '22222222-2222-4222-8222-222222222222';
    foreach (['Bluesky', 'Mastodon', 'YouTube', 'Telegram'] as $module) {
        $class = 'App\\Modules\\'.$module.'\\Analytics\\Reports\\Jobs\\GenerateAnalyticsReport';
        $fixture['jobs'][strtolower($module)] = serialize(new $class(1, $token));
    }
    $fixture['jobs']['site_intel'] = serialize(new GenerateSiteIntelReport(1, $token));
    file_put_contents($root.'/tests/Fixtures/refactor-upgrade-baseline.json', json_encode($fixture, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
} finally {
    foreach ($temporaryFiles as $temporaryFile) {
        unlink($temporaryFile);
    }
}
