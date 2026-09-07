<?php

declare(strict_types=1);

namespace Tests\Feature\MoonShine;

use App\Models\User;
use App\MoonShine\Resources\AppUser\AppUserResource;
use App\MoonShine\Resources\ParserRun\ParserRunResource;
use App\MoonShine\Support\AdminRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Laravel\Models\MoonshineUserRole;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('moonshine.access.enforce_ip_allowlist', false);
    }

    #[Test]
    public function developer_sees_the_dashboard_and_operations_but_not_user_management(): void
    {
        $developer = $this->staff(AdminRole::Developer);
        $this->actingAs($developer, 'moonshine');

        $this->get(route('moonshine.index'))
            ->assertOk()
            ->assertSee(__('admin_dashboard.hero.title'))
            ->assertSee(__('admin_panel.resources.parser_runs'))
            ->assertDontSee(__('admin_panel.resources.registered_users'));

        $this->get($this->resourceUrl(ParserRunResource::class))->assertOk();
        $this->get($this->resourceUrl(AppUserResource::class))->assertForbidden();
    }

    #[Test]
    public function analyst_can_read_product_data_but_cannot_open_edit_forms(): void
    {
        $analyst = $this->staff(AdminRole::Analyst);
        $this->actingAs($analyst, 'moonshine');

        $this->get($this->resourceUrl(AppUserResource::class))->assertOk();

        $user = User::factory()->create();

        $this->get(app(AppUserResource::class)->getFormPageUrl($user->getKey()))
            ->assertForbidden();
    }

    #[Test]
    public function staff_member_with_unknown_role_cannot_open_the_dashboard(): void
    {
        $role = MoonshineUserRole::query()->create(['name' => 'Legacy']);
        $staff = MoonshineUser::query()->create([
            'moonshine_user_role_id' => $role->getKey(),
            'name' => 'Legacy staff member',
            'email' => 'legacy@example.test',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($staff, 'moonshine')
            ->get(route('moonshine.index'))
            ->assertForbidden();
    }

    /**
     * @param  class-string  $resourceClass
     */
    private function resourceUrl(string $resourceClass): string
    {
        return app($resourceClass)->getUrl();
    }

    private function staff(AdminRole $role): MoonshineUser
    {
        $roleModel = MoonshineUserRole::query()->firstOrCreate([
            'name' => $role->databaseName(),
        ]);

        return MoonshineUser::query()->create([
            'moonshine_user_role_id' => $roleModel->getKey(),
            'name' => $role->databaseName(),
            'email' => $role->value.'@example.test',
            'password' => Hash::make('password'),
        ]);
    }
}
