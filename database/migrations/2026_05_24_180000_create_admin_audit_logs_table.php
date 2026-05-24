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
        Schema::create('admin_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('actor_admin_id')->nullable();
            $table->string('actor_admin_name', 191)->nullable();
            $table->string('target_type', 120);
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('action', 80);
            $table->json('changes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['target_type', 'target_id'], 'admin_audit_target_idx');
            $table->index('action', 'admin_audit_action_idx');
            $table->index('created_at', 'admin_audit_created_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
    }
};
