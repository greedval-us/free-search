<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TelegramTrackingMigrationTest extends TestCase
{
    use DatabaseMigrations;

    protected function migrateFreshUsing(): array
    {
        return ['--path' => [
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/2026_09_10_120000_create_telegram_tracking_tables.php',
        ]];
    }

    public function test_search_migration_preserves_legacy_progress_and_collected_messages(): void
    {
        $started = '2026-09-09 10:00:00';
        $windowEnd = '2026-09-09 16:00:00';
        $userId = DB::table('users')->insertGetId(['name' => 'Test', 'email' => 'user@example.test', 'password' => 'unused']);
        $taskId = DB::table('telegram_trackings')->insertGetId([
            'user_id' => $userId, 'name' => 'Legacy task', 'mode' => 'keyword', 'query' => 'query',
            'activation_plan' => 'free', 'started_at' => $started, 'expires_at' => '2026-10-09 10:00:00',
        ]);
        $source = ['tracking_id' => $taskId, 'session_name' => 'test', 'title' => 'Group',
            'collect_from' => $started, 'next_check_at' => $windowEnd];
        $active = DB::table('telegram_tracking_sources')->insertGetId($source + [
            'peer_id' => '101', 'window_end' => $windowEnd, 'cursor_id' => 5, 'offset_id' => 10,
            'high_id' => 12, 'pending_matches' => 1, 'checked_at' => $started,
        ]);
        $idle = DB::table('telegram_tracking_sources')->insertGetId($source + ['peer_id' => '102']);
        DB::table('telegram_tracking_messages')->insert([
            'tracking_id' => $taskId, 'source_id' => $active, 'message_id' => 10,
            'text' => 'Saved match', 'sent_at' => $started, 'received_at' => $windowEnd,
        ]);

        $migration = require database_path('migrations/2026_09_10_130000_add_search_windows_to_telegram_tracking_sources.php');
        $migration->up();

        $this->assertDatabaseHas('telegram_tracking_sources', [
            'id' => $active, 'window_start' => $started, 'collection_method' => 'history',
            'window_end' => $windowEnd, 'cursor_id' => 5, 'offset_id' => 10,
            'high_id' => 12, 'pending_matches' => 1, 'checked_at' => $started,
        ]);
        $this->assertDatabaseHas('telegram_tracking_sources', ['id' => $idle, 'window_start' => null, 'collection_method' => null]);
        $this->assertDatabaseHas('telegram_tracking_messages', ['source_id' => $active, 'message_id' => 10, 'text' => 'Saved match']);
        $this->assertDatabaseCount('telegram_tracking_sources', 2);
        $this->assertDatabaseCount('telegram_tracking_messages', 1);
    }
}
