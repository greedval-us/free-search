<?php

namespace Tests\Unit;

use App\Modules\ParserSupport\ParserRunConfig;
use PHPUnit\Framework\TestCase;

class ParserRunConfigTest extends TestCase
{
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
