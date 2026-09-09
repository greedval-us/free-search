<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_bot_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('telegraph_chat_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('telegram_id')->unique();
            $table->string('locale', 2)->default('en');
            $table->boolean('notifications_enabled')->default(true);
            $table->boolean('exports_enabled')->default(false);
            $table->boolean('broadcasts_enabled')->default(false);
            $table->timestamps();
        });
        Schema::create('telegram_bot_link_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->string('telegram_id')->nullable();
            $table->foreignId('telegraph_chat_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('locale', 2);
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
        Schema::create('telegram_bot_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('feature');
            $table->char('fingerprint', 64);
            $table->json('parameters');
            $table->timestamp('expires_at')->index();
            $table->timestamps();
            $table->unique(['user_id', 'fingerprint']);
        });
        Schema::create('telegram_bot_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('link_id')->constrained('telegram_bot_links')->cascadeOnDelete();
            $table->char('deduplication_key', 64)->unique();
            $table->string('kind', 24);
            $table->string('reference')->nullable();
            $table->json('payload')->nullable();
            $table->boolean('automatic')->default(true);
            $table->string('status', 16)->default('pending')->index();
            $table->string('error_code')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_bot_deliveries');
        Schema::dropIfExists('telegram_bot_reports');
        Schema::dropIfExists('telegram_bot_link_requests');
        Schema::dropIfExists('telegram_bot_links');
    }
};
