<?php

namespace Tests\Feature;

use App\Models\FeatureUsageDaily;
use App\Models\User;
use App\Models\YouTubeAnalyticsSchedule;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\Support\QuotaConcurrencyDatabase;
use Tests\TestCase;

class FeatureUsageConcurrencyTest extends TestCase
{
    public function test_parallel_debits_respect_the_limit_and_repeated_refunds_release_only_one_operation(): void
    {
        $this->useDedicatedDatabase();
        $user = User::factory()->create();
        $moment = now('UTC')->toIso8601String();

        try {
            $receipts = array_values(array_filter($this->runWorkers($user, array_fill(0, 6, ['consume', '']), $moment)));
            $this->assertCount(2, $receipts);
            $this->assertSame(2, (int) FeatureUsageDaily::query()->where('user_id', $user->id)->sum('used'));

            $results = $this->runWorkers($user, [
                ['refund', $receipts[0]], ['refund', $receipts[0]], ['refund', $receipts[0]],
                ['consume', ''], ['consume', ''], ['consume', ''],
            ], $moment);

            $successfulDebits = count(array_filter($results));
            $this->assertLessThanOrEqual(1, $successfulDebits);
            $this->assertSame(1 + $successfulDebits, (int) FeatureUsageDaily::query()->where('user_id', $user->id)->sum('used'));
            $this->assertDatabaseHas('feature_usage_receipts', ['id' => $receipts[1], 'released_at' => null]);
            $this->assertNotNull(DB::table('feature_usage_receipts')->where('id', $receipts[0])->value('released_at'));
        } finally {
            $user->delete();
            DB::disconnect('quota_concurrency');
        }
    }

    public function test_parallel_schedule_creation_cannot_exceed_the_owner_limit(): void
    {
        $this->useDedicatedDatabase();
        $user = User::factory()->create();

        try {
            $results = $this->runWorkers($user, array_fill(0, 6, ['schedule', '']), now('UTC')->toIso8601String());

            $counts = array_count_values($results);
            ksort($counts);
            $this->assertSame(['created' => 2, 'schedule_limit' => 4], $counts);
            $this->assertSame(2, YouTubeAnalyticsSchedule::query()->forUser($user->id)->count());
            $this->assertDatabaseMissing('feature_usage_daily', ['user_id' => $user->id]);
        } finally {
            $user->delete();
            DB::disconnect('quota_concurrency');
        }
    }

    private function useDedicatedDatabase(): void
    {
        $configuration = QuotaConcurrencyDatabase::configuration();
        if ($configuration === null) {
            $this->markTestSkipped('Requires explicitly configured dedicated MySQL/MariaDB quota concurrency test database; SQLite cannot prove row locking.');
        }
        config()->set('database.connections.quota_concurrency', $configuration);
        config()->set('database.default', 'quota_concurrency');
        $this->artisan('migrate', ['--database' => 'quota_concurrency', '--force' => true])->assertSuccessful();
        $this->assertSame('InnoDB', DB::selectOne("SHOW TABLE STATUS WHERE Name = 'feature_usage_daily'")->Engine);
    }

    /**
     * @param  list<array{string, string}>  $operations
     * @return list<?string>
     */
    private function runWorkers(User $user, array $operations, string $moment): array
    {
        $barrier = tempnam(sys_get_temp_dir(), 'quota-concurrency-');
        unlink($barrier);
        $processes = [];
        try {
            foreach ($operations as $index => [$operation, $receiptId]) {
                $process = new Process([
                    PHP_BINARY, base_path('tests/Support/quota-concurrency-worker.php'),
                    $operation, (string) $user->id, $receiptId, $barrier, (string) $index, $moment,
                ], base_path(), ['APP_ENV' => 'testing']);
                $process->setTimeout(30);
                $process->start();
                $processes[] = $process;
            }
            $deadline = microtime(true) + 15;
            foreach ($processes as $index => $process) {
                while (! is_file($barrier.'.ready.'.$index)) {
                    if (! $process->isRunning() || microtime(true) >= $deadline) {
                        $this->fail('Quota worker did not reach barrier: '.$process->getErrorOutput());
                    }
                    usleep(1000);
                }
            }
            file_put_contents($barrier, 'run');
            $results = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
                $this->assertMatchesRegularExpression('/QUOTA_RESULT:(.*)/', $process->getOutput());
                preg_match('/QUOTA_RESULT:(.*)/', $process->getOutput(), $match);
                $results[] = json_decode($match[1], true, flags: JSON_THROW_ON_ERROR);
            }

            return $results;
        } finally {
            foreach ($processes as $index => $process) {
                $process->stop();
                if (is_file($barrier.'.ready.'.$index)) {
                    unlink($barrier.'.ready.'.$index);
                }
            }
            if (is_file($barrier)) {
                unlink($barrier);
            }
        }
    }
}
