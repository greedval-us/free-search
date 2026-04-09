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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('account_type', ['user', 'admin', 'moderator'])->default('user')->after('password');
            $table->boolean('is_premium')->default(false)->after('account_type');
            $table->timestamp('premium_expires_at')->nullable()->after('is_premium');
            $table->string('telegram_id')->nullable()->unique()->after('premium_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'account_type',
                'is_premium',
                'premium_expires_at',
                'telegram_id',
            ]);
        });
    }
};
