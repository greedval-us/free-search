<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_usage_daily', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('feature', 80);
            $table->date('usage_date');
            $table->unsignedInteger('used')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'feature', 'usage_date'], 'feature_usage_daily_unique');
            $table->index(['feature', 'usage_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_usage_daily');
    }
};
