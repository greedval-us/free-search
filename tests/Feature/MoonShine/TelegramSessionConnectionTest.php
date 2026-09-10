<?php

declare(strict_types=1);

namespace Tests\Feature\MoonShine;

use App\Models\AdminAuditLog;
use App\Models\TelegramSessionConnection;
use App\Models\User;
use App\MoonShine\Pages\TelegramSessionsPage;
use App\MoonShine\Support\AdminRole;
use App\Support\MadelineProto\Authentication\LoginResult;
use App\Support\MadelineProto\Authentication\LoginStage;
use App\Support\MadelineProto\Authentication\SessionAuthenticator;
use App\Support\MadelineProto\Authentication\SessionConnectionService;
use App\Support\MadelineProto\Authentication\SessionFiles;
use App\Support\MadelineProto\MadelineProtoConfig;
use App\Support\MadelineProto\MadelineProtoSessionPool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Laravel\Models\MoonshineUserRole;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TelegramSessionConnectionTest extends TestCase
{
    use RefreshDatabase;

    private string $directory;

    private SessionAuthenticator $authenticator;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->directory = 'framework/testing/admin-sessions-'.Str::uuid();
        config()->set([
            'moonshine.access.enforce_ip_allowlist' => false,
            'madelineproto.api_id' => 1, 'madelineproto.api_hash' => 'PRIVATE_API_HASH',
            'madelineproto.session_path' => $this->directory.'/sessions',
            'madelineproto.log_path' => $this->directory.'/logs/madeline.log',
            'madelineproto.admin_auth.requests_per_minute' => 30,
        ]);
        $this->app->forgetInstance(MadelineProtoConfig::class);
        $this->authenticator = new class(app(MadelineProtoConfig::class)) implements SessionAuthenticator
        {
            public LoginResult $result;

            public int $calls = 0;

            public function __construct(private MadelineProtoConfig $config)
            {
                $this->result = new LoginResult(LoginStage::Code);
            }

            public function submit(string $name, LoginStage $stage, string $value): LoginResult
            {
                $this->calls++;
                File::ensureDirectoryExists($this->config->sessionFilePathFor($name));

                return $this->result;
            }

            public function inspect(string $name): LoginResult
            {
                $this->calls++;

                return $this->result;
            }
        };
        $this->app->instance(SessionAuthenticator::class, $this->authenticator);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path($this->directory));
        parent::tearDown();
    }

    public static function roles(): iterable
    {
        yield 'admin' => [AdminRole::Admin, 200, 302];
        yield 'developer' => [AdminRole::Developer, 200, 403];
        yield 'analyst' => [AdminRole::Analyst, 403, 403];
    }

    #[Test]
    #[DataProvider('roles')]
    public function pages_and_mutations_enforce_staff_roles(AdminRole $role, int $viewStatus, int $writeStatus): void
    {
        $this->actingAs($this->staff($role), 'moonshine');
        $this->get($this->page())->assertStatus($viewStatus);
        $this->getJson($this->page(), ['X-MS-Structure' => 'true'])->assertStatus($viewStatus);
        $this->post($this->endpoint('store'), ['name' => 'worker-a'])->assertStatus($writeStatus);
        if ($role !== AdminRole::Admin) {
            foreach (['phone', 'code', 'password', 'inspect'] as $action) {
                $this->post($this->endpoint($action, 1), [])->assertForbidden();
            }
        }
        self::assertSame(0, $this->authenticator->calls);
    }

    #[Test]
    public function guest_and_website_user_cannot_manage_service_accounts(): void
    {
        $this->get($this->page())->assertRedirect();
        $this->post($this->endpoint('store'), ['name' => 'worker-a'])->assertRedirect();
        $this->actingAs(User::factory()->create());
        $this->post($this->endpoint('store'), ['name' => 'worker-a'])->assertRedirect();
        self::assertSame(0, TelegramSessionConnection::query()->count());
    }

    #[Test]
    public function complete_two_factor_flow_publishes_only_after_confirmed_authentication(): void
    {
        $this->actingAs($this->staff(AdminRole::Admin), 'moonshine');
        $this->post($this->endpoint('store'), ['name' => 'worker-a'])->assertRedirect();
        $connection = TelegramSessionConnection::query()->sole();
        self::assertTrue(app(SessionFiles::class)->pending('worker-a'));
        $this->post($this->endpoint('phone', $connection->id), ['phone_number' => '+15555551234'])->assertRedirect();
        self::assertSame(LoginStage::Code, $connection->fresh()->stage);
        self::assertSame([], app(MadelineProtoSessionPool::class)->availableSessionNames());
        $this->get($this->page($connection->id))->assertOk()->assertSee('name="phone_code"', false)->assertDontSee('+15555551234');

        $this->authenticator->result = new LoginResult(LoginStage::Password);
        $this->post($this->endpoint('code', $connection->id), ['phone_code' => '76543'])->assertRedirect();
        $this->get($this->page($connection->id))->assertOk()->assertSee('name="password"', false)->assertDontSee('76543');
        self::assertSame([], app(MadelineProtoSessionPool::class)->availableSessionNames());

        $this->authenticator->result = new LoginResult(LoginStage::Ready);
        $this->post($this->endpoint('password', $connection->id), ['password' => 'PRIVATE_PASSWORD'])->assertRedirect();
        self::assertSame(LoginStage::Ready, $connection->fresh()->stage);
        self::assertSame(['worker-a'], app(MadelineProtoSessionPool::class)->availableSessionNames());
        self::assertFalse(app(SessionFiles::class)->pending('worker-a'));
        foreach ([$this->get($this->page($connection->id)), $this->getJson($this->page($connection->id), ['X-MS-Structure' => 'true'])] as $response) {
            $response->assertOk()->assertDontSee('PRIVATE_', false)->assertDontSee('+15555551234');
        }
        $metadata = TelegramSessionConnection::all()->toJson().AdminAuditLog::all()->toJson().json_encode(session()->all());
        foreach (['PRIVATE_PASSWORD', 'PRIVATE_API_HASH', '+15555551234', '76543'] as $secret) {
            self::assertStringNotContainsString($secret, $metadata);
        }
    }

    #[Test]
    public function code_can_complete_login_without_two_factor_password(): void
    {
        $connection = $this->connection();
        $this->authenticator->result = new LoginResult(LoginStage::Ready);
        $this->post($this->endpoint('code', $connection->id), ['phone_code' => '76543'])->assertRedirect();
        self::assertSame(['worker-a'], app(MadelineProtoSessionPool::class)->availableSessionNames());
    }

    #[Test]
    public function another_admin_cannot_read_or_continue_someone_elses_attempt(): void
    {
        $connection = $this->connection();
        $calls = $this->authenticator->calls;
        $this->actingAs($this->staff(AdminRole::Admin), 'moonshine');
        $this->get($this->page($connection->id))->assertNotFound();
        $this->getJson($this->page($connection->id), ['X-MS-Structure' => 'true'])->assertNotFound();
        $this->post($this->endpoint('code', $connection->id), ['phone_code' => '76543'])->assertForbidden();
        $this->post($this->endpoint('inspect', $connection->id))->assertForbidden();
        self::assertSame($calls, $this->authenticator->calls);
    }

    #[Test]
    public function unsafe_names_and_existing_disk_sessions_cannot_be_overwritten(): void
    {
        $this->actingAs($this->staff(AdminRole::Admin), 'moonshine');
        foreach (['../default', 'Worker-A', 'a/b', 'a.b', str_repeat('x', 80)] as $name) {
            $this->post($this->endpoint('store'), ['name' => $name])->assertSessionHasErrors('name');
        }
        $path = app(MadelineProtoConfig::class)->sessionFilePathFor('default');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'EXISTING_SESSION');
        $this->post($this->endpoint('store'), ['name' => 'default'])->assertSessionHas('telegram_session_error', 'exists');
        self::assertSame('EXISTING_SESSION', File::get($path));
        self::assertSame(0, TelegramSessionConnection::query()->count());
        self::assertSame(['default'], app(MadelineProtoSessionPool::class)->availableSessionNames());
    }

    #[Test]
    public function expired_attempt_requires_a_new_phone_request_and_never_enters_pool(): void
    {
        $connection = $this->connection();
        $this->travel((int) config('madelineproto.admin_auth.ttl_minutes') + 1)->minutes();
        $this->post($this->endpoint('code', $connection->id), ['phone_code' => '76543'])->assertSessionHas('telegram_session_error', 'expired');
        $this->post($this->endpoint('inspect', $connection->id))->assertSessionHas('telegram_session_error', 'expired');
        self::assertSame([], app(MadelineProtoSessionPool::class)->availableSessionNames());
        $this->post($this->endpoint('phone', $connection->id), ['phone_number' => '+15555551234'])->assertRedirect();
        self::assertFalse($connection->fresh()->expired());
        self::assertSame(LoginStage::Code, $connection->fresh()->stage);
    }

    #[Test]
    public function flood_wait_is_persisted_and_enforced_for_every_step(): void
    {
        $connection = $this->connection();
        $this->authenticator->result = new LoginResult(null, 'flood_wait', 120);
        $this->post($this->endpoint('code', $connection->id), ['phone_code' => '76543'])->assertRedirect();
        $calls = $this->authenticator->calls;
        $this->post($this->endpoint('phone', $connection->id), ['phone_number' => '+15555551234'])->assertSessionHas('telegram_session_error', 'flood_wait');
        $this->post($this->endpoint('inspect', $connection->id))->assertSessionHas('telegram_session_error', 'flood_wait');
        self::assertSame($calls, $this->authenticator->calls);
        self::assertTrue($connection->fresh()->retry_at->isFuture());
    }

    #[Test]
    public function wrong_code_returns_to_phone_but_wrong_password_can_be_retried(): void
    {
        $connection = $this->connection();
        $this->authenticator->result = new LoginResult(LoginStage::Phone, 'code_invalid');
        $this->post($this->endpoint('code', $connection->id), ['phone_code' => '00000'])->assertRedirect();
        $this->get($this->page($connection->id))->assertSee('name="phone_number"', false);
        self::assertSame([], app(MadelineProtoSessionPool::class)->availableSessionNames());

        $connection->update(['stage' => LoginStage::Password]);
        $this->authenticator->result = new LoginResult(null, 'password_invalid');
        $this->post($this->endpoint('password', $connection->id), ['password' => 'PRIVATE_BAD_PASSWORD'])->assertRedirect();
        $this->get($this->page($connection->id))->assertSee('name="password"', false)->assertDontSee('PRIVATE_BAD_PASSWORD');
        self::assertSame(LoginStage::Password, $connection->fresh()->stage);
    }

    #[Test]
    public function credentials_are_not_flashed_after_validation_errors(): void
    {
        $connection = $this->connection();
        foreach (['phone' => ['phone_number' => 'PRIVATE_PHONE'], 'code' => ['phone_code' => 'PRIVATE_CODE'], 'password' => ['password' => str_repeat('PRIVATE_PASSWORD', 25)]] as $step => $payload) {
            $this->from($this->page($connection->id))->post($this->endpoint($step, $connection->id), $payload)->assertSessionHasErrors();
            self::assertStringNotContainsString('PRIVATE_', json_encode(session()->getOldInput()));
        }
    }

    #[Test]
    public function inspect_recovers_confirmation_and_quarantines_a_revoked_session(): void
    {
        $connection = $this->connection();
        $this->authenticator->result = new LoginResult(LoginStage::Ready);
        $this->post($this->endpoint('inspect', $connection->id))->assertRedirect();
        self::assertSame(['worker-a'], app(MadelineProtoSessionPool::class)->availableSessionNames());
        $this->authenticator->result = new LoginResult(LoginStage::Phone);
        $this->post($this->endpoint('inspect', $connection->id))->assertRedirect();
        self::assertSame([], app(MadelineProtoSessionPool::class)->availableSessionNames());
        self::assertTrue(app(SessionFiles::class)->pending('worker-a'));
    }

    #[Test]
    public function local_lock_rejects_concurrent_requests_for_the_same_session(): void
    {
        $connection = $this->connection();
        $calls = $this->authenticator->calls;
        app(SessionFiles::class)->locked('worker-a', function () use ($connection): void {
            $this->post($this->endpoint('code', $connection->id), ['phone_code' => '76543'])->assertSessionHas('telegram_session_error', 'busy');
        });
        self::assertSame($calls, $this->authenticator->calls);
    }

    #[Test]
    public function reserved_catalog_like_name_is_still_usable_and_cli_cannot_touch_pending_sessions(): void
    {
        $staff = $this->staff(AdminRole::Admin);
        $connection = app(SessionConnectionService::class)->create('admin-auth-catalog', $staff->id);
        self::assertSame(LoginStage::Phone, $connection->stage);
        $this->artisan('app:create-telegram-session', ['name' => $connection->name])->assertFailed();
        self::assertSame(0, $this->authenticator->calls);
    }

    #[Test]
    public function a_ready_session_cannot_have_its_credentials_replaced(): void
    {
        $connection = $this->connection();
        $connection->update(['stage' => LoginStage::Ready]);
        $calls = $this->authenticator->calls;
        $this->post($this->endpoint('phone', $connection->id), ['phone_number' => '+15555551234'])->assertSessionHas('telegram_session_error', 'already_ready');
        self::assertSame($calls, $this->authenticator->calls);
    }

    #[Test]
    public function insecure_production_request_cannot_send_telegram_credentials(): void
    {
        $connection = $this->connection();
        $this->app->instance('env', 'production');
        $calls = $this->authenticator->calls;
        $token = Str::random(40);
        $this->withSession(['_token' => $token])->post($this->endpoint('phone', $connection->id), ['phone_number' => '+15555551234', '_token' => $token])->assertForbidden();
        self::assertSame($calls, $this->authenticator->calls);
    }

    #[Test]
    public function missing_csrf_token_is_rejected_even_for_authenticated_admin(): void
    {
        $this->actingAs($this->staff(AdminRole::Admin), 'moonshine');
        $this->app->instance('env', 'local');
        $this->post($this->endpoint('store'), ['name' => 'worker-a'])->assertStatus(419);
        self::assertSame(0, TelegramSessionConnection::query()->count());
    }

    #[Test]
    public function repeated_authentication_requests_are_throttled_before_calling_telegram(): void
    {
        config()->set('madelineproto.admin_auth.requests_per_minute', 1);
        $connection = $this->connection();
        $this->post($this->endpoint('inspect', $connection->id))->assertRedirect();
        $calls = $this->authenticator->calls;
        $this->post($this->endpoint('inspect', $connection->id))->assertStatus(429);
        self::assertSame($calls, $this->authenticator->calls);
    }

    #[Test]
    public function missing_configuration_and_connection_limits_fail_without_new_session_files(): void
    {
        $this->actingAs($this->staff(AdminRole::Admin), 'moonshine');
        config()->set('madelineproto.admin_auth.max_sessions', 0);
        $this->post($this->endpoint('store'), ['name' => 'worker-a'])->assertSessionHas('telegram_session_error', 'limit');
        self::assertFalse(app(SessionFiles::class)->pending('worker-a'));
        config()->set('madelineproto.api_hash', null);
        $this->app->forgetInstance(MadelineProtoConfig::class);
        $this->post($this->endpoint('store'), ['name' => 'worker-a'])->assertSessionHas('telegram_session_error', 'configuration');
        self::assertSame(0, TelegramSessionConnection::query()->count());
    }

    private function connection(): TelegramSessionConnection
    {
        $staff = $this->staff(AdminRole::Admin);
        $this->actingAs($staff, 'moonshine');
        $connection = app(SessionConnectionService::class)->create('worker-a', $staff->id);
        app(SessionConnectionService::class)->submit($connection->id, $staff->id, LoginStage::Phone, '+15555551234');

        return $connection->fresh();
    }

    private function staff(AdminRole $role): MoonshineUser
    {
        return MoonshineUser::query()->create([
            'moonshine_user_role_id' => MoonshineUserRole::firstOrCreate(['name' => $role->databaseName()])->id,
            'name' => $role->databaseName(), 'email' => Str::uuid().'@example.test', 'password' => Hash::make('password'),
        ]);
    }

    private function page(?int $id = null): string
    {
        return app(TelegramSessionsPage::class)->getUrl().($id === null ? '' : '?connection='.$id);
    }

    private function endpoint(string $action, ?int $id = null): string
    {
        return route('moonshine.telegram-sessions.'.$action, $id === null ? [] : ['connection' => $id]);
    }
}
