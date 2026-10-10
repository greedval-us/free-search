<?php

namespace Tests\Unit;

use App\Modules\ParserSupport\ParserRunConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ParserRunConfigTest extends TestCase
{
    #[DataProvider('recoveryPassLimits')]
    public function test_recovery_pass_budget_and_scheduler_lease_stay_bounded(int $configured, int $seconds, int $minutes): void
    {
        $config = ParserRunConfig::fromArray(['recovery' => ['max_pass_seconds' => $configured]]);

        $this->assertSame($seconds, $config->recoveryMaxPassSeconds());
        $this->assertSame($minutes, $config->recoveryMutexMinutes());
        $this->assertGreaterThan($seconds + 150, $minutes * 60);
    }

    public static function recoveryPassLimits(): array
    {
        return [[0, 1, 5], [30, 30, 5], [3600, 3600, 65], [1000000, 3600, 65]];
    }

    public function test_it_normalizes_all_parser_run_settings(): void
    {
        $config = ParserRunConfig::fromArray([
            'retention_days' => 0,
            'history_limit' => -1,
            'cleanup_batch_size' => 0,
            'cleanup_schedule' => 'invalid',
            'queue' => [
                'enabled' => false,
                'name' => ' parser-runs ',
                'step_delay_seconds' => -1,
            ],
        ]);

        $this->assertFalse($config->queueEnabled());
        $this->assertSame('parser-runs', $config->queueName());
        $this->assertSame(0, $config->stepDelaySeconds());
        $this->assertSame(1, $config->retentionDays());
        $this->assertSame(1, $config->historyLimit());
        $this->assertSame(1, $config->cleanupBatchSize());
        $this->assertSame('03:30', $config->cleanupSchedule());
    }

    public function test_it_accepts_valid_cleanup_schedule(): void
    {
        $config = ParserRunConfig::fromArray(['cleanup_schedule' => '23:59']);

        $this->assertSame('23:59', $config->cleanupSchedule());
    }
}
