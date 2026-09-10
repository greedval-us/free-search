<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_tracking_sources', function (Blueprint $table): void {
            $table->timestamp('window_start')->nullable();
            $table->string('collection_method', 16)->nullable();
        });

        // Finish in-flight history pages before switching their next window to search.
        DB::table('telegram_tracking_sources')->whereNotNull('window_end')->update([
            'window_start' => DB::raw('collect_from'),
            'collection_method' => 'history',
        ]);
    }

    public function down(): void
    {
        Schema::table('telegram_tracking_sources', function (Blueprint $table): void {
            $table->dropColumn(['window_start', 'collection_method']);
        });
    }
};
