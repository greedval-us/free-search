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
        Schema::create('user_saved_queries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('module_key', 120);
            $table->string('query_preview', 255);
            $table->string('method', 10)->default('GET');
            $table->string('path', 500);
            $table->text('run_url')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'module_key', 'created_at'], 'user_saved_queries_user_module_created_at_idx');
            $table->index(['user_id', 'last_used_at'], 'user_saved_queries_user_last_used_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_saved_queries');
    }
};

