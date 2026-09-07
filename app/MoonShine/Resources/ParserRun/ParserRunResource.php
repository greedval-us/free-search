<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\ParserRun;

use App\Models\ParserRun;
use App\MoonShine\Resources\ParserRun\Pages\ParserRunIndexPage;
use App\MoonShine\Resources\Shared\ReadOnlyModelResource;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;

/**
 * @extends ReadOnlyModelResource<ParserRun, ParserRunIndexPage, null, null>
 */
#[Icon('arrow-path-rounded-square')]
#[Order(20)]
final class ParserRunResource extends ReadOnlyModelResource
{
    protected string $model = ParserRun::class;

    protected string $column = 'run_id';

    protected bool $simplePaginate = true;

    public function getTitle(): string
    {
        return __('admin_panel.resources.parser_runs');
    }

    protected function pages(): array
    {
        return [
            ParserRunIndexPage::class,
        ];
    }

    protected function search(): array
    {
        return [
            'id',
            'run_id',
            'module',
            'status',
            'stage',
            'error',
            'user.email',
        ];
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        $builder->with('user:id,email');

        if (! $this->hasQueryParam('sort')) {
            $builder->orderByDesc('started_at')->orderByDesc('id');
        }

        return $builder;
    }
}
