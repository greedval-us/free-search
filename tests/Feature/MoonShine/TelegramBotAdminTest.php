<?php

declare(strict_types=1);

namespace Tests\Feature\MoonShine;

use App\Modules\TelegramBot\Models\BotDelivery;
use App\Modules\TelegramBot\Models\LinkRequest;
use App\MoonShine\Pages\TelegramBotOverviewPage;
use App\MoonShine\Resources\TelegramBotDelivery\TelegramBotDeliveryResource;
use App\MoonShine\Resources\TelegramBotLink\TelegramBotLinkResource;
use App\MoonShine\Support\AdminRole;
use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Support\Facades\Hash;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Laravel\Models\MoonshineUserRole;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\TelegramBot\TelegramBotTestCase;

final class TelegramBotAdminTest extends TelegramBotTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('moonshine.access.enforce_ip_allowlist', false);
        config()->set('telegram_bot.queue.connection', 'sync');
    }

    public static function roles(): iterable
    {
        yield 'admin' => [AdminRole::Admin, 200, 200];
        yield 'analyst' => [AdminRole::Analyst, 200, 403];
        yield 'developer' => [AdminRole::Developer, 403, 200];
    }

    #[Test]
    #[DataProvider('roles')]
    public function pages_enforce_role_permissions(AdminRole $role, int $linksStatus, int $deliveriesStatus): void
    {
        $this->actingAs($this->staff($role), 'moonshine');
        $this->get(app(TelegramBotOverviewPage::class)->getUrl())
            ->assertOk()->assertSee(__('admin_bot.overview'))->assertSee(__('admin_bot.empty'));
        $this->get(app(TelegramBotLinkResource::class)->getUrl())->assertStatus($linksStatus);
        $this->get(app(TelegramBotDeliveryResource::class)->getUrl())->assertStatus($deliveriesStatus);
        $this->getJson(app(TelegramBotOverviewPage::class)->getUrl(), ['X-MS-Structure' => 'true'])->assertOk();
        $this->getJson(app(TelegramBotLinkResource::class)->getUrl(), ['X-MS-Structure' => 'true'])->assertStatus($linksStatus);
        $this->getJson(app(TelegramBotDeliveryResource::class)->getUrl(), ['X-MS-Structure' => 'true'])->assertStatus($deliveriesStatus);
        $this->getJson(route('moonshine.crud.index', ['resourceUri' => app(TelegramBotLinkResource::class)->getUriKey()]))->assertStatus($linksStatus);
        $this->getJson(route('moonshine.crud.index', ['resourceUri' => app(TelegramBotDeliveryResource::class)->getUriKey()]))->assertStatus($deliveriesStatus);
        Telegraph::assertNothingSent();
    }

    #[Test]
    public function overview_rejects_guests_and_unknown_staff_roles(): void
    {
        $url = app(TelegramBotOverviewPage::class)->getUrl();
        $this->get($url)->assertRedirect();
        $staff = $this->staff(AdminRole::Analyst);
        $staff->moonshineUserRole->update(['name' => 'Unknown']);
        $this->actingAs($staff->fresh(), 'moonshine');
        $this->get($url)->assertForbidden();
        $this->getJson($url, ['X-MS-Structure' => 'true'])->assertForbidden();
    }

    #[Test]
    public function analyst_overview_does_not_inspect_the_queue(): void
    {
        config()->set('telegram_bot.queue.connection', 'redis');
        $this->mock(Factory::class)->shouldNotReceive('connection');
        $this->actingAs($this->staff(AdminRole::Analyst), 'moonshine')
            ->get(app(TelegramBotOverviewPage::class)->getUrl())
            ->assertOk()->assertDontSee(__('admin_bot.diagnostics'));
    }

    #[Test]
    public function admin_pages_do_not_expose_credentials_payloads_or_raw_errors(): void
    {
        $link = $this->linkedUser();
        $tokenHash = hash('sha256', 'private-link-token');
        LinkRequest::query()->create([
            'user_id' => $link->user_id, 'token_hash' => $tokenHash, 'locale' => 'en', 'expires_at' => now()->addMinutes(10),
        ]);
        BotDelivery::query()->create([
            'link_id' => $link->id, 'kind' => 'broadcast', 'reference' => 'PRIVATE_REFERENCE',
            'deduplication_key' => 'PRIVATE_DEDUPLICATION', 'payload' => ['text' => 'PRIVATE_MESSAGE'],
            'status' => BotDelivery::FAILED, 'error_code' => 'PRIVATE_ERROR_TOKEN',
        ]);
        $this->actingAs($this->staff(AdminRole::Admin), 'moonshine');
        foreach ([TelegramBotOverviewPage::class, TelegramBotLinkResource::class, TelegramBotDeliveryResource::class] as $class) {
            $url = app($class)->getUrl();
            foreach ([$this->get($url), $this->getJson($url, ['X-MS-Structure' => 'true'])] as $response) {
                $response->assertOk();
                foreach ([$this->bot->token, config('telegram_bot.webhook_secret'), $tokenHash, 'PRIVATE_REFERENCE', 'PRIVATE_DEDUPLICATION', 'PRIVATE_MESSAGE', 'PRIVATE_ERROR_TOKEN'] as $secret) {
                    self::assertFalse(str_contains($response->getContent(), $secret), "Sensitive value exposed by $class");
                }
            }
        }
        $this->getJson(route('moonshine.crud.index', ['resourceUri' => app(TelegramBotDeliveryResource::class)->getUriKey()]))
            ->assertOk()->assertDontSee('PRIVATE_', false);
        Telegraph::assertNothingSent();
    }

    #[Test]
    public function developer_sees_delivery_metadata_without_account_identity(): void
    {
        $link = $this->linkedUser('19876543210');
        BotDelivery::query()->create([
            'link_id' => $link->id, 'kind' => 'parser', 'reference' => 'private-run',
            'deduplication_key' => 'private-run-delivery', 'payload' => [],
            'status' => BotDelivery::FAILED, 'error_code' => 'file_unavailable',
        ]);
        $this->actingAs($this->staff(AdminRole::Developer), 'moonshine');
        foreach ([TelegramBotOverviewPage::class, TelegramBotDeliveryResource::class] as $class) {
            $this->get(app($class)->getUrl())->assertOk()
                ->assertDontSee($link->user->email)->assertDontSee($link->telegram_id);
        }
        $this->get(app(TelegramBotDeliveryResource::class)->getUrl())
            ->assertSee(__('admin_bot.errors.file_unavailable'));
    }

    #[Test]
    public function even_administrator_cannot_mutate_bot_data_through_crud_routes(): void
    {
        $link = $this->linkedUser();
        $delivery = BotDelivery::query()->create([
            'link_id' => $link->id, 'kind' => 'notification', 'reference' => 'test',
            'deduplication_key' => 'test', 'payload' => [],
        ]);
        $this->actingAs($this->staff(AdminRole::Admin), 'moonshine');
        foreach ([TelegramBotLinkResource::class => $link, TelegramBotDeliveryResource::class => $delivery] as $class => $model) {
            $parameters = ['resourceUri' => app($class)->getUriKey()];
            $itemParameters = [...$parameters, 'resourceItem' => $model->id];
            $this->getJson(route('moonshine.crud.show', $itemParameters))->assertForbidden();
            $this->postJson(route('moonshine.crud.store', $parameters), [])->assertForbidden();
            $this->putJson(route('moonshine.crud.update', $itemParameters), ['status' => 'sent'])->assertForbidden();
            $this->deleteJson(route('moonshine.crud.destroy', $itemParameters))->assertForbidden();
            $this->deleteJson(route('moonshine.crud.massDelete', $parameters), ['ids' => [$model->id]])->assertForbidden();
            $this->putJson(route('moonshine.update-field.through-column', $itemParameters), ['field' => 'status', 'value' => 'sent'])->assertForbidden();
            $this->assertDatabaseHas($model->getTable(), ['id' => $model->id]);
        }
        self::assertSame(BotDelivery::PENDING, $delivery->fresh()->status);
        Telegraph::assertNothingSent();
    }

    #[Test]
    public function account_filters_support_disabled_preferences_and_email_search(): void
    {
        $enabled = $this->linkedUser('10001');
        $disabled = $this->linkedUser('10002', ['notifications_enabled' => false]);
        $this->actingAs($this->staff(AdminRole::Analyst), 'moonshine');
        $url = route('moonshine.crud.index', ['resourceUri' => app(TelegramBotLinkResource::class)->getUriKey()]);
        $this->getJson($url.'?'.http_build_query(['filter' => ['notifications_enabled' => '0']]))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $disabled->id);
        $this->getJson($url.'?'.http_build_query(['search' => $enabled->user->email, 'reset' => 1]))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $enabled->id);
    }

    #[Test]
    public function deliveries_can_be_filtered_by_status_and_manual_mode(): void
    {
        $link = $this->linkedUser();
        foreach ([true, false] as $automatic) {
            BotDelivery::query()->create([
                'link_id' => $link->id, 'kind' => 'parser', 'reference' => 'test',
                'deduplication_key' => 'test-'.(int) $automatic, 'payload' => [],
                'status' => $automatic ? BotDelivery::SENT : BotDelivery::FAILED, 'automatic' => $automatic,
            ]);
        }
        $this->actingAs($this->staff(AdminRole::Developer), 'moonshine');
        $url = route('moonshine.crud.index', ['resourceUri' => app(TelegramBotDeliveryResource::class)->getUriKey()]);
        $this->getJson($url.'?'.http_build_query(['filter' => ['status' => 'failed', 'automatic' => '0']]))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.status', 'failed')->assertJsonPath('data.0.automatic', false);
    }

    private function staff(AdminRole $role): MoonshineUser
    {
        $roleModel = MoonshineUserRole::query()->firstOrCreate(['name' => $role->databaseName()]);

        return MoonshineUser::query()->create([
            'moonshine_user_role_id' => $roleModel->id,
            'name' => $role->databaseName(), 'email' => $role->value.'@example.test',
            'password' => Hash::make('password'),
        ]);
    }
}
