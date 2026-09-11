<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_trackings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('mode', 16);
            $table->string('query', 120);
            $table->string('status', 24)->default('active');
            $table->string('pause_reason', 32)->nullable();
            $table->string('activation_plan', 8);
            $table->timestamp('entitlement_until')->nullable();
            $table->boolean('notify_bot')->default(false);
            $table->timestamp('started_at');
            $table->timestamp('expires_at')->index();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('purge_at')->nullable()->index();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
        Schema::create('telegram_tracking_sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tracking_id')->constrained('telegram_trackings')->cascadeOnDelete();
            $table->string('session_name', 64);
            $table->string('peer_id', 32);
            $table->string('username', 64)->nullable();
            $table->string('title');
            $table->unsignedBigInteger('cursor_id')->default(0);
            $table->unsignedBigInteger('offset_id')->default(0);
            $table->unsignedBigInteger('high_id')->default(0);
            $table->timestamp('collect_from');
            $table->timestamp('window_end')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamp('next_check_at')->index();
            $table->uuid('lease_token')->nullable();
            $table->timestamp('lease_until')->nullable();
            $table->string('error_code', 32)->nullable();
            $table->unsignedInteger('failure_count')->default(0);
            $table->unsignedInteger('pending_matches')->default(0);
            $table->timestamps();
            $table->unique(['tracking_id', 'peer_id']);
        });
        Schema::create('telegram_tracking_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tracking_id')->constrained('telegram_trackings')->cascadeOnDelete();
            $table->foreignId('source_id')->constrained('telegram_tracking_sources')->cascadeOnDelete();
            $table->unsignedBigInteger('message_id');
            $table->string('sender_id', 32)->nullable();
            $table->text('text');
            $table->timestamp('sent_at');
            $table->timestamp('received_at');
            $table->unique(['source_id', 'message_id']);
            $table->index(['tracking_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_tracking_messages');
        Schema::dropIfExists('telegram_tracking_sources');
        Schema::dropIfExists('telegram_trackings');
    }
};
