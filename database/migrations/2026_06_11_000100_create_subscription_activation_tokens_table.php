<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_activation_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('token', 24)->unique();
            $table->string('plan', 20);
            $table->unsignedInteger('duration_days')->default(30);
            $table->string('note')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->foreignId('used_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('used_subscription_id')->nullable()->constrained('user_subscriptions')->nullOnDelete();
            $table->timestamps();

            $table->index(['plan', 'used_at']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_activation_tokens');
    }
};
