<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_intel_report_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->json('targets');
            $table->string('report_type', 16);
            $table->unsignedTinyInteger('crawl_limit')->default(8);
            $table->string('platform_type', 24)->default('auto');
            $table->string('interval', 8);
            $table->string('send_time', 5);
            $table->string('timezone', 64);
            $table->boolean('send_to_bot')->default(false);
            $table->boolean('enabled')->default(true);
            $table->timestamp('next_run_at');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['enabled', 'next_run_at']);
            $table->index(['user_id', 'deleted_at']);
        });

        Schema::create('site_intel_scheduled_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_id')->constrained('site_intel_report_schedules')->cascadeOnDelete();
            $targetUrl = $table->string('target_url', 512);
            if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                $targetUrl->charset('utf8mb4')->collation('utf8mb4_bin');
            }
            $table->string('report_type', 16);
            $table->unsignedTinyInteger('crawl_limit')->default(8);
            $table->string('platform_type', 24)->default('auto');
            $table->timestamp('scheduled_for');
            $table->timestamp('date_from');
            $table->timestamp('date_to');
            $table->string('status', 16)->default('pending');
            $table->json('data')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('completion_notified_at')->nullable();
            $table->boolean('is_manual')->default(false);
            $table->boolean('quota_charged')->default(false);
            $table->timestamp('quota_charged_at')->nullable();
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->timestamp('available_at')->nullable();
            $table->uuid('lease_token')->nullable();
            $table->timestamp('lease_until')->nullable();
            $table->timestamps();
            $table->unique(['schedule_id', 'target_url', 'scheduled_for'], 'site_intel_report_occurrence');
            $table->index(['status', 'available_at', 'lease_until'], 'site_intel_report_due');
            $table->index(['user_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_intel_scheduled_reports');
        Schema::dropIfExists('site_intel_report_schedules');
    }
};
