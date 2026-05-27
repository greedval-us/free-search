<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'is_premium')) {
                $table->dropColumn('is_premium');
            }

            if (Schema::hasColumn('users', 'premium_expires_at')) {
                $table->dropColumn('premium_expires_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'is_premium')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('is_premium')->default(false)->after('account_type');
            });
        }

        if (! Schema::hasColumn('users', 'premium_expires_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->timestamp('premium_expires_at')->nullable()->after('is_premium');
            });
        }
    }
};
