<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\FailedJob;

use App\Models\FailedJob;
use App\MoonShine\Resources\FailedJob\Pages\FailedJobIndexPage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;

/**
 * @extends ModelResource<FailedJob, FailedJobIndexPage, null, null>
 */
#[Icon('exclamation-circle')]
#[Order(31)]
class FailedJobResource extends ModelResource
{
    protected string $model = FailedJob::class;

    protected string $column = 'queue';

    protected bool $simplePaginate = true;

    public function getTitle(): string
    {
        return __('admin_panel.resources.failed_jobs');
    }

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->except(
            Action::CREATE,
            Action::VIEW,
            Action::UPDATE,
        );
    }

    protected function pages(): array
    {
        return [
            FailedJobIndexPage::class,
        ];
    }

    protected function search(): array
    {
        return [
            'id',
            'uuid',
            'queue',
            'connection',
        ];
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->orderByDesc('failed_at');
    }
}
