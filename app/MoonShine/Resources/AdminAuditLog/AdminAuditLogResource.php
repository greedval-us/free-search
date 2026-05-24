<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\AdminAuditLog;

use App\Models\AdminAuditLog;
use App\MoonShine\Resources\AdminAuditLog\Pages\AdminAuditLogIndexPage;
use App\MoonShine\Resources\Shared\ReadOnlyModelResource;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;

/**
 * @extends ReadOnlyModelResource<AdminAuditLog, AdminAuditLogIndexPage, null, null>
 */
#[Icon('shield-check')]
#[Order(40)]
class AdminAuditLogResource extends ReadOnlyModelResource
{
    protected string $model = AdminAuditLog::class;

    protected string $column = 'action';

    protected bool $simplePaginate = true;

    public function getTitle(): string
    {
        return __('admin_panel.resources.admin_audit_log');
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
        if (!$this->hasQueryParam('sort')) {
            $builder->orderByDesc('created_at');
        }

        return $builder;
    }
}
