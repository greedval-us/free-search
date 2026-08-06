<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parser_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('run_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('module', 32);
            $table->string('status', 32);
            $table->string('stage', 64)->nullable();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->text('error')->nullable();
            $table->string('file_disk', 32)->default('private');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'module']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parser_runs');
    }
};
