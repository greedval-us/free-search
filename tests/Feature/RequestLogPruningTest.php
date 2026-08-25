<?php

namespace Tests\Feature;

use App\Models\RequestLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class RequestLogPruningTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_request_logs_older_than_retention_period_are_pruned(): void
    {
        Config::set('activity.retention_days', 30);

        $oldLog = $this->createLog(now()->subDays(31));
        $recentLog = $this->createLog(now()->subDays(29));

        $this->artisan('model:prune', [
            '--model' => [RequestLog::class],
        ])->assertSuccessful();

        $this->assertDatabaseMissing('request_logs', ['id' => $oldLog->id]);
        $this->assertDatabaseHas('request_logs', ['id' => $recentLog->id]);
    }

    private function createLog(mixed $createdAt): RequestLog
    {
        return RequestLog::query()->create([
            'method' => 'GET',
            'path' => '/test',
            'created_at' => $createdAt,
        ]);
    }
}
