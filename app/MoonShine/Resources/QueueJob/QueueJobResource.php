<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\QueueJob;

use App\Models\QueueJob;
use App\MoonShine\Resources\QueueJob\Pages\QueueJobIndexPage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;

/**
 * @extends ModelResource<QueueJob, QueueJobIndexPage, null, null>
 */
#[Icon('server-stack')]
#[Order(30)]
class QueueJobResource extends ModelResource
{
    protected string $model = QueueJob::class;

    protected string $column = 'queue';

    protected bool $simplePaginate = true;

    public function getTitle(): string
    {
        return __('admin_panel.resources.queue_jobs');
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
            QueueJobIndexPage::class,
        ];
    }

    protected function search(): array
    {
        return [
            'id',
            'queue',
            'attempts',
        ];
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->orderByDesc('id');
    }
}
