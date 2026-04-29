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
        Schema::table('request_logs', function (Blueprint $table): void {
            $table->string('module_key', 120)->nullable()->after('route_name');
            $table->string('action_key', 160)->nullable()->after('module_key');
            $table->string('query_preview', 255)->nullable()->after('request_data');
            $table->json('metadata')->nullable()->after('response_data');

            $table->index(
                ['user_id', 'module_key', 'created_at'],
                'request_logs_user_module_created_at_idx'
            );
            $table->index(
                ['route_name', 'created_at'],
                'request_logs_route_created_at_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_logs', function (Blueprint $table): void {
            $table->dropIndex('request_logs_user_module_created_at_idx');
            $table->dropIndex('request_logs_route_created_at_idx');

            $table->dropColumn([
                'module_key',
                'action_key',
                'query_preview',
                'metadata',
            ]);
        });
    }
};

