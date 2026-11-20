<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PREVIOUS_LENGTH = 24;

    public function up(): void
    {
        Schema::table('telegram_bot_deliveries', function (Blueprint $table): void {
            $table->string('kind')->change();
        });
    }

    public function down(): void
    {
        // In SQL LIKE, each underscore matches one character on both MySQL and SQLite.
        $tooLong = str_repeat('_', self::PREVIOUS_LENGTH + 1).'%';
        if (DB::table('telegram_bot_deliveries')->where('kind', 'like', $tooLong)->exists()) {
            throw new RuntimeException('Cannot narrow delivery kinds while longer kinds are stored in telegram_bot_deliveries.');
        }

        Schema::table('telegram_bot_deliveries', function (Blueprint $table): void {
            $table->string('kind', self::PREVIOUS_LENGTH)->change();
        });
    }
};
