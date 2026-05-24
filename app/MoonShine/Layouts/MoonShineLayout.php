<?php

declare(strict_types=1);

namespace App\MoonShine\Layouts;

use App\MoonShine\Support\AdminNavigationCatalog;
use MoonShine\ColorManager\ColorManager;
use MoonShine\ColorManager\Palettes\PurplePalette;
use MoonShine\Contracts\ColorManager\ColorManagerContract;
use MoonShine\Contracts\ColorManager\PaletteContract;
use MoonShine\Contracts\MenuManager\MenuElementContract;
use MoonShine\Laravel\Layouts\AppLayout;
use MoonShine\MenuManager\MenuGroup;
use MoonShine\MenuManager\MenuItem;

final class MoonShineLayout extends AppLayout
{
    /**
     * @var null|class-string<PaletteContract>
     */
    protected ?string $palette = PurplePalette::class;

    protected function assets(): array
    {
        return [
            ...parent::assets(),
        ];
    }

    protected function menu(): array
    {
        $groups = [];

        foreach (AdminNavigationCatalog::menuGroups() as $group) {
            $groups[] = MenuGroup::make(
                $group['title'],
                $this->menuItems($group['resources'])
            );
        }

        return $groups;
    }

    /**
     * @param array<int, class-string> $resources
     * @return list<MenuElementContract>
     */
    private function menuItems(array $resources): array
    {
        return array_map(
            static fn (string $resourceClass): MenuElementContract => MenuItem::make($resourceClass),
            $resources,
        );
    }

    /**
     * @param  ColorManager  $colorManager
     */
    protected function colors(ColorManagerContract $colorManager): void
    {
        parent::colors($colorManager);

        // $colorManager->primary('#00000');
    }
}
