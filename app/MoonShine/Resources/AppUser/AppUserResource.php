<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\AppUser;

use App\Models\User;
use App\MoonShine\Resources\AppUser\Pages\AppUserFormPage;
use App\MoonShine\Resources\AppUser\Pages\AppUserIndexPage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\MenuManager\Attributes\Group;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;

/**
 * @extends ModelResource<User, AppUserIndexPage, AppUserFormPage, null>
 */
#[Icon('users')]
#[Group('moonshine::ui.resource.system', 'users', translatable: true)]
#[Order(10)]
class AppUserResource extends ModelResource
{
    protected string $model = User::class;

    protected string $column = 'email';

    protected bool $simplePaginate = true;

    public function getTitle(): string
    {
        return 'Registered Users';
    }

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->except(
            Action::CREATE,
            Action::VIEW,
            Action::DELETE,
            Action::MASS_DELETE,
        );
    }

    protected function pages(): array
    {
        return [
            AppUserIndexPage::class,
            AppUserFormPage::class,
        ];
    }

    protected function search(): array
    {
        return [
            'id',
            'name',
            'email',
            'account_type',
            'telegram_id',
            'is_blocked',
            'is_premium',
        ];
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder
            ->withCount('requestLogs')
            ->orderByDesc('created_at');
    }
}
