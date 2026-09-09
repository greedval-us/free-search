<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Laravel\Models\MoonshineUserRole;
use RuntimeException;
use Tests\TestCase;

class MoonShineStaffRoleRollbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_rollback_does_not_promote_staff_to_administrator(): void
    {
        MoonshineUserRole::query()->firstOrCreate(['name' => 'Admin']);
        $analyst = MoonshineUserRole::query()->firstOrCreate(['name' => 'Analyst']);
        $user = MoonshineUser::query()->create([
            'moonshine_user_role_id' => $analyst->id,
            'name' => 'Analyst',
            'email' => 'analyst@example.test',
            'password' => 'not-used',
        ]);
        $migration = require database_path('migrations/2026_09_07_000001_add_moonshine_staff_roles.php');
        try {
            $migration->down();
            $this->fail('Rollback must require an explicit role reassignment.');
        } catch (RuntimeException) {
            $this->assertSame($analyst->id, $user->fresh()->moonshine_user_role_id);
            $this->assertDatabaseHas('moonshine_user_roles', ['id' => $analyst->id]);
        }
    }
}
