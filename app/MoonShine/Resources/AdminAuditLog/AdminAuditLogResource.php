<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\AdminAuditLog;

use App\Models\AdminAuditLog;
use App\MoonShine\Resources\AdminAuditLog\Pages\AdminAuditLogIndexPage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;

/**
 * @extends ModelResource<AdminAuditLog, AdminAuditLogIndexPage, null, null>
 */
#[Icon('shield-check')]
#[Order(40)]
class AdminAuditLogResource extends ModelResource
{
    protected string $model = AdminAuditLog::class;

    protected string $column = 'action';

    protected bool $simplePaginate = true;

    public function getTitle(): string
    {
        return 'Admin Audit Log';
    }

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->except(
            Action::CREATE,
            Action::VIEW,
            Action::UPDATE,
            Action::DELETE,
            Action::MASS_DELETE,
        );
    }

    protected function pages(): array
    {
        return [
            AdminAuditLogIndexPage::class,
        ];
    }

    protected function search(): array
    {
        return [
            'id',
            'actor_admin_name',
            'target_type',
            'target_id',
            'action',
        ];
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->orderByDesc('created_at');
    }
}
