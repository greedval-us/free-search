<?php

declare(strict_types=1);

use App\MoonShine\Support\AdminRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([AdminRole::Analyst, AdminRole::Developer] as $role) {
            if (DB::table('moonshine_user_roles')->where('name', $role->databaseName())->doesntExist()) {
                DB::table('moonshine_user_roles')->insert([
                    'name' => $role->databaseName(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $adminRoleId = DB::table('moonshine_user_roles')
            ->where('name', AdminRole::Admin->databaseName())
            ->value('id');

        $staffRoleIds = DB::table('moonshine_user_roles')
            ->whereIn('name', [
                AdminRole::Analyst->databaseName(),
                AdminRole::Developer->databaseName(),
            ])
            ->pluck('id');

        if ($adminRoleId !== null && $staffRoleIds->isNotEmpty()) {
            DB::table('moonshine_users')
                ->whereIn('moonshine_user_role_id', $staffRoleIds)
                ->update(['moonshine_user_role_id' => $adminRoleId]);
        }

        DB::table('moonshine_user_roles')
            ->whereIn('id', $staffRoleIds)
            ->delete();
    }
};
