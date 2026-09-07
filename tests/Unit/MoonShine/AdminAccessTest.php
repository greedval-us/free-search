<?php

declare(strict_types=1);

namespace Tests\Unit\MoonShine;

use App\MoonShine\Resources\AppUser\AppUserResource;
use App\MoonShine\Resources\ParserRun\ParserRunResource;
use App\MoonShine\Resources\RequestLog\RequestLogResource;
use App\MoonShine\Support\AdminAccess;
use App\MoonShine\Support\AdminRole;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Laravel\Models\MoonshineUserRole;
use MoonShine\Support\Enums\Ability;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AdminAccessTest extends TestCase
{
    private AdminAccess $access;

    protected function setUp(): void
    {
        parent::setUp();

        $this->access = new AdminAccess;
    }

    #[Test]
    public function administrator_has_full_access(): void
    {
        $user = $this->user(AdminRole::Admin);

        self::assertTrue($this->access->allows($user, AppUserResource::class, Ability::UPDATE));
        self::assertTrue($this->access->allows($user, RequestLogResource::class, Ability::DELETE));
    }

    #[Test]
    public function analyst_only_reads_product_and_usage_resources(): void
    {
        $user = $this->user(AdminRole::Analyst);

        self::assertTrue($this->access->allows($user, AppUserResource::class, Ability::VIEW_ANY));
        self::assertTrue($this->access->allows($user, ParserRunResource::class, Ability::VIEW_ANY));
        self::assertFalse($this->access->allows($user, AppUserResource::class, Ability::UPDATE));
        self::assertFalse($this->access->allows($user, RequestLogResource::class, Ability::VIEW_ANY));
    }

    #[Test]
    public function developer_reads_operations_without_user_management_access(): void
    {
        $user = $this->user(AdminRole::Developer);

        self::assertTrue($this->access->allows($user, ParserRunResource::class, Ability::VIEW_ANY));
        self::assertTrue($this->access->allows($user, RequestLogResource::class, Ability::VIEW_ANY));
        self::assertFalse($this->access->allows($user, AppUserResource::class, Ability::VIEW_ANY));
        self::assertFalse($this->access->allows($user, ParserRunResource::class, Ability::DELETE));
    }

    #[Test]
    public function unknown_role_is_denied_by_default(): void
    {
        $user = new MoonshineUser;
        $user->setRelation('moonshineUserRole', (new MoonshineUserRole)->forceFill(['name' => 'Custom']));

        self::assertNull($this->access->role($user));
        self::assertFalse($this->access->allows($user, ParserRunResource::class, Ability::VIEW_ANY));
    }

    private function user(AdminRole $role): MoonshineUser
    {
        $user = new MoonshineUser;
        $user->setRelation(
            'moonshineUserRole',
            (new MoonshineUserRole)->forceFill(['name' => $role->databaseName()]),
        );

        return $user;
    }
}
