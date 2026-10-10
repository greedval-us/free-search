<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const REPORTS = [
        'bluesky_analytics_reports' => 'bluesky.analytics',
        'mastodon_analytics_reports' => 'mastodon.analytics',
        'telegram_analytics_reports' => 'telegram.analytics',
        'youtube_analytics_reports' => 'youtube.analytics',
        'site_intel_scheduled_reports' => null,
    ];

    public function up(): void
    {
        Schema::create('feature_usage_receipts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('feature_usage_daily_id')->constrained('feature_usage_daily')->cascadeOnDelete();
            $table->timestamp('created_at');
            $table->timestamp('released_at')->nullable();
        });

        foreach (self::REPORTS as $tableName => $resource) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->uuid('quota_receipt_id')->nullable();
            });

            // Only unfinished legacy reports can still enter the refund path.
            // Workers and in-flight requests must be stopped before this migration.
            $columns = ['reports.id', 'reports.user_id', 'reports.quota_charged_at'];
            if ($resource === null) {
                $columns[] = 'reports.report_type';
            }
            DB::table($tableName.' as reports')
                ->join('users', 'users.id', '=', 'reports.user_id')
                ->whereNotIn('users.account_type', config('access.bypass_account_types', []))
                ->where('reports.quota_charged', true)
                ->whereNotNull('reports.quota_charged_at')
                ->whereIn('reports.status', ['pending', 'processing'])
                ->select($columns)
                ->chunkById(100, function ($reports) use ($tableName, $resource): void {
                    foreach ($reports as $report) {
                        $resourceKey = $resource ?? 'site-intel.'.$report->report_type;
                        $resources = config('access.resources', []);
                        $policy = data_get($resources, $resourceKey) ?? ($resources[$resourceKey] ?? []);
                        $quotaKey = $policy['quota_key'] ?? $resourceKey;
                        $usageDate = Carbon::parse($report->quota_charged_at, config('app.timezone'))->toDateString();

                        DB::transaction(function () use ($report, $tableName, $quotaKey, $usageDate): void {
                            $usage = DB::table('feature_usage_daily')
                                ->where('user_id', $report->user_id)
                                ->where('feature', $quotaKey)
                                ->whereDate('usage_date', $usageDate)
                                ->where('used', '>', 0)
                                ->lockForUpdate()->first(['id']);
                            if ($usage === null) {
                                return;
                            }

                            $receiptId = (string) Str::uuid();
                            DB::table('feature_usage_receipts')->insert([
                                'id' => $receiptId,
                                'feature_usage_daily_id' => $usage->id,
                                'created_at' => $report->quota_charged_at,
                            ]);
                            DB::table($tableName)->where('id', $report->id)
                                ->update(['quota_receipt_id' => $receiptId]);
                        });
                    }
                }, 'reports.id', 'id');
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::REPORTS) as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('quota_receipt_id');
            });
        }

        Schema::dropIfExists('feature_usage_receipts');
    }
};
