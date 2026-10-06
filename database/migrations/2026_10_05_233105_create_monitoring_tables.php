<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monitoring_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('mode')->default('overview');
            $table->json('filters');
            $table->string('language', 8)->default('ru');
            $table->string('timezone', 64);
            $table->string('status')->default('active');
            $table->unsignedInteger('generation')->default(1);
            $table->boolean('collection_enabled')->default(true);
            $table->boolean('delivery_enabled')->default(false);
            $table->boolean('attach_files')->default(false);
            $table->string('empty_delivery')->default('send');
            $table->unsignedInteger('collect_interval_minutes')->default(360);
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
        Schema::create('monitoring_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('monitoring_projects')->cascadeOnDelete();
            $table->string('platform', 16);
            $table->text('input');
            $table->string('identity', 512)->nullable();
            $table->string('identity_hash', 64)->nullable();
            $table->string('title')->default('');
            $table->json('configuration')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('generation')->default(1);
            $table->text('cursor')->nullable();
            foreach (['collect_from', 'last_collected_at', 'next_collect_at', 'coverage_start', 'coverage_end', 'lease_until', 'dispatched_at'] as $column) {
                $table->timestamp($column)->nullable();
            }
            $table->string('error')->nullable();
            $table->json('warnings')->nullable();
            $table->uuid('lease_token')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamps();
            $table->index('next_collect_at');
            $table->unique(['project_id', 'platform', 'identity_hash'], 'monitoring_source_identity');
        });
        Schema::create('monitoring_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('monitoring_projects')->cascadeOnDelete();
            $table->string('period', 16);
            $table->boolean('enabled')->default(true);
            $table->boolean('delivery_enabled')->default(false);
            $table->string('time', 5)->default('09:00');
            $table->string('timezone', 64);
            $table->date('anchor_date');
            $table->timestamp('next_run_at')->nullable()->index();
            $table->timestamp('last_run_at')->nullable();
            $table->unsignedInteger('generation')->default(1);
            $table->unsignedInteger('missed_runs')->default(0);
            $table->timestamps();
        });
        Schema::create('monitoring_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained('monitoring_sources')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('trigger_key', 64)->unique();
            $table->unsignedInteger('generation');
            $table->unsignedInteger('source_generation');
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->string('status')->default('queued');
            $table->text('cursor')->nullable();
            $table->json('cursor_history')->nullable();
            $table->json('warnings')->nullable();
            foreach (['pages', 'items_count', 'attempts'] as $column) {
                $table->unsignedInteger($column)->default(0);
            }
            $table->uuid('lease_token')->nullable();
            foreach (['lease_until', 'next_attempt_at', 'dispatched_at', 'completed_at'] as $column) {
                $table->timestamp($column)->nullable();
            }
            $table->string('error')->nullable();
            $table->timestamps();
            $table->index(['status', 'next_attempt_at']);
            $table->index(['source_id', 'generation', 'start_at']);
        });
        Schema::create('monitoring_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('monitoring_projects')->cascadeOnDelete();
            $table->foreignId('source_id')->constrained('monitoring_sources')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('platform', 16);
            $table->text('external_id');
            $table->string('external_hash', 64);
            $table->text('url');
            $table->text('title')->nullable();
            $table->text('text');
            $table->string('author', 512)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('collected_at');
            $table->json('metrics')->nullable();
            $table->unsignedInteger('generation');
            $table->timestamps();
            $table->unique(['source_id', 'external_hash']);
            $table->index(['project_id', 'published_at', 'id'], 'monitoring_material_period');
            $table->index(['user_id', 'collected_at']);
        });
        Schema::create('monitoring_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('monitoring_projects')->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('monitoring_schedules')->nullOnDelete();
            $table->string('trigger_key', 64);
            $table->string('request_key', 64)->nullable()->unique();
            $table->unsignedInteger('version')->default(1);
            $table->string('status')->default('queued');
            $table->string('period', 16);
            foreach (['start_at', 'end_at', 'cutoff_at', 'expires_at'] as $column) {
                $table->timestamp($column);
            }
            $table->string('timezone', 64);
            $table->json('configuration');
            $table->json('summary')->nullable();
            $table->json('coverage')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('delivery_status')->nullable();
            $table->string('file_status')->default('pending');
            $table->json('files')->nullable();
            $table->uuid('lease_token')->nullable();
            foreach (['lease_until', 'next_attempt_at', 'dispatched_at'] as $column) {
                $table->timestamp($column)->nullable();
            }
            $table->unsignedInteger('attempts')->default(0);
            $table->string('error')->nullable();
            $table->timestamps();
            $table->unique(['trigger_key', 'version']);
            $table->index(['project_id', 'created_at', 'id']);
            $table->index(['status', 'next_attempt_at']);
            $table->index('expires_at');
        });
        Schema::create('monitoring_report_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('monitoring_reports')->cascadeOnDelete();
            $table->foreignId('material_id')->nullable()->constrained('monitoring_materials')->nullOnDelete();
            $table->json('snapshot');
            $table->unique(['report_id', 'material_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['monitoring_report_items', 'monitoring_reports', 'monitoring_materials', 'monitoring_collections', 'monitoring_schedules', 'monitoring_sources', 'monitoring_projects'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
