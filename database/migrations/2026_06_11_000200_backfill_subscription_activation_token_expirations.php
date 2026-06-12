<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('subscription_activation_tokens')
            ->select(['id', 'created_at'])
            ->whereNull('expires_at')
            ->orderBy('id')
            ->chunkById(100, function ($tokens): void {
                foreach ($tokens as $token) {
                    DB::table('subscription_activation_tokens')
                        ->where('id', $token->id)
                        ->update([
                            'expires_at' => Carbon::parse($token->created_at)->addMonth(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('subscription_activation_tokens')
            ->update([
                'expires_at' => null,
            ]);
    }
};
