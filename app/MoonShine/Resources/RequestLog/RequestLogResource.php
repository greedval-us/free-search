<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\RequestLog;

use App\Models\RequestLog;
use App\MoonShine\Resources\RequestLog\Pages\RequestLogIndexPage;
use App\MoonShine\Resources\Shared\ReadOnlyModelResource;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\MenuManager\Attributes\Group;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;

/**
 * @extends ReadOnlyModelResource<RequestLog, RequestLogIndexPage, null, null>
 */
#[Icon('clock')]
#[Group('moonshine::ui.resource.system', 'users', translatable: true)]
#[Order(11)]
class RequestLogResource extends ReadOnlyModelResource
{
    protected string $model = RequestLog::class;

    protected string $column = 'query_preview';

    protected array $with = ['user'];

    protected bool $simplePaginate = true;

    public function getTitle(): string
    {
        return __('admin_panel.resources.user_activity');
    }

    protected function pages(): array
    {
        return [
            RequestLogIndexPage::class,
        ];
    }

    protected function search(): array
    {
        return [
            'id',
            'method',
            'path',
            'route_name',
            'module_key',
            'action_key',
            'query_preview',
            'ip_address',
            'user.name',
            'user.email',
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
