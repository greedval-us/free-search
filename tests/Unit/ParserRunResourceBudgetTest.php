<?php

namespace Tests\Unit;

use App\Modules\ParserSupport\ParserRunConfig;
use App\Modules\ParserSupport\ParserRunLifecycleManager;
use App\Modules\ParserSupport\ParserRunResourceBudget;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ParserRunResourceBudgetTest extends TestCase
{
    #[DataProvider('terminalReasons')]
    public function test_finalization_preserves_an_exact_limit_checkpoint_with_measured_shipped_metadata_overhead(string $locale, string $reason): void
    {
        app()->setLocale($locale);
        $budget = new ParserRunResourceBudget(ParserRunConfig::fromArray(['limits' => ['max_checkpoint_bytes' => 4096]]), new ParserRunLifecycleManager);
        $before = ['status' => 'running', 'resources' => ['stepAttempts' => 1]];
        $atLimit = [...$before, 'data' => ['content' => str_repeat('x', 4023)]];
        $accepted = $budget->constrainCheckpoint($before, $atLimit, null);
        $savedBytes = strlen(json_encode($accepted, JSON_UNESCAPED_UNICODE));
        $this->assertSame(4096, $savedBytes);

        $failed = $budget->fail($accepted, $reason, null);
        $terminalBytes = strlen(json_encode($failed, JSON_UNESCAPED_UNICODE));

        $this->assertSame($accepted['data'], $failed['data']);
        $this->assertSame('failed', $failed['status']);
        $this->assertSame('failed', $failed['stage']);
        $this->assertSame($reason, $failed['resources']['exhausted']);
        $this->assertSame(__('errors.api.parser_run.limit_'.$reason), $failed['error']);
        $this->assertNull($failed['result']);
        $this->assertGreaterThan($savedBytes, $terminalBytes);
        $this->assertLessThanOrEqual(1024, $terminalBytes - $savedBytes);
    }

    public static function terminalReasons(): array
    {
        $cases = [];
        foreach (['en', 'ru'] as $locale) {
            foreach (['duration', 'step_attempts', 'checkpoint_bytes', 'records', 'source_requests'] as $reason) {
                $cases[$locale.' '.$reason] = [$locale, $reason];
            }
        }

        return $cases;
    }

    public function test_a_checkpoint_exactly_at_the_byte_limit_is_kept_and_one_byte_over_is_failed(): void
    {
        $budget = new ParserRunResourceBudget(ParserRunConfig::fromArray(['limits' => ['max_checkpoint_bytes' => 4096]]), new ParserRunLifecycleManager);
        $before = ['status' => 'running', 'resources' => ['stepAttempts' => 1]];
        $atLimit = [...$before, 'data' => ['content' => str_repeat('x', 4023)]];
        $this->assertSame(4096, strlen(json_encode($atLimit)));

        $accepted = $budget->constrainCheckpoint($before, $atLimit, null);
        $rejected = $budget->constrainCheckpoint($before, [...$atLimit, 'data' => ['content' => str_repeat('x', 4024)]], null);

        $this->assertSame($atLimit, $accepted);
        $this->assertSame('failed', $rejected['status']);
        $this->assertSame('checkpoint_bytes', $rejected['resources']['exhausted']);
        $this->assertArrayNotHasKey('data', $rejected);
    }
}
