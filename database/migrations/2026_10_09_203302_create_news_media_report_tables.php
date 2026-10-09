<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_media_report_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->json('queries');
            $table->string('brand', 80)->default('');
            $table->json('competitors');
            $table->string('domain', 253)->default('');
            $table->json('search_options');
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

        Schema::create('news_media_scheduled_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_id')->constrained('news_media_report_schedules')->cascadeOnDelete();
            $query = $table->string('query', 180);
            if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                $query->charset('utf8mb4')->collation('utf8mb4_bin');
            }
            $table->string('brand', 80)->default('');
            $table->json('competitors');
            $table->string('domain', 253)->default('');
            $table->json('search_options');
            $table->timestamp('scheduled_for');
            $table->string('status', 16)->default('pending');
            $table->json('data')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('completion_notified_at')->nullable();
            $table->boolean('is_manual')->default(false);
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->timestamp('available_at')->nullable();
            $table->uuid('lease_token')->nullable();
            $table->timestamp('lease_until')->nullable();
            $table->timestamps();
            $table->unique(['schedule_id', 'query', 'scheduled_for'], 'news_media_report_occurrence');
            $table->index(['status', 'available_at', 'lease_until'], 'news_media_report_due');
            $table->index(['user_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_media_scheduled_reports');
        Schema::dropIfExists('news_media_report_schedules');
    }
};
