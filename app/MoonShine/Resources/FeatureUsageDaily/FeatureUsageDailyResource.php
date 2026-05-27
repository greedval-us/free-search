<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\FeatureUsageDaily;

use App\Models\FeatureUsageDaily;
use App\MoonShine\Resources\FeatureUsageDaily\Pages\FeatureUsageDailyIndexPage;
use App\MoonShine\Resources\Shared\ReadOnlyModelResource;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;

/**
 * @extends ReadOnlyModelResource<FeatureUsageDaily, FeatureUsageDailyIndexPage, null, null>
 */
#[Icon('chart-bar')]
#[Order(6)]
class FeatureUsageDailyResource extends ReadOnlyModelResource
{
    protected string $model = FeatureUsageDaily::class;

    protected string $column = 'feature';

    protected bool $simplePaginate = true;

    public function getTitle(): string
    {
        return __('admin_panel.resources.feature_usage_daily');
    }

    protected function pages(): array
    {
        return [
            FeatureUsageDailyIndexPage::class,
        ];
    }

    protected function search(): array
    {
        return [
            'id',
            'feature',
            'user.email',
        ];
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        $builder->with(['user']);

        if (! $this->hasQueryParam('sort')) {
            $builder->orderByDesc('usage_date');
        }

        return $builder;
    }
}
