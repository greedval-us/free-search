<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan', 20);
            $table->string('status', 20)->default('active');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'starts_at', 'ends_at'], 'user_subscriptions_active_idx');
            $table->index(['plan', 'status']);
        });

        $now = now();
        DB::table('users')
            ->where('is_premium', true)
            ->orderBy('id')
            ->select(['id', 'premium_expires_at'])
            ->chunkById(100, function ($users) use ($now): void {
                foreach ($users as $user) {
                    DB::table('user_subscriptions')->insert([
                        'user_id' => $user->id,
                        'plan' => 'pro',
                        'status' => 'active',
                        'starts_at' => $now,
                        'ends_at' => $user->premium_expires_at ?? $now->copy()->addMonth(),
                        'metadata' => json_encode(['source' => 'legacy_premium']),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};
