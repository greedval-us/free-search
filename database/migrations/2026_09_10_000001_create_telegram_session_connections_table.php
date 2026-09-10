<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_session_connections', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 64)->unique();
            $table->unsignedBigInteger('created_by')->index();
            $table->string('stage', 24);
            $table->string('last_error', 48)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('retry_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_session_connections');
    }
};
