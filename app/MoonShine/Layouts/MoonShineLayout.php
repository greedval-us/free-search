<?php

declare(strict_types=1);

namespace App\MoonShine\Layouts;

use App\MoonShine\Pages\Dashboard;
use App\MoonShine\Support\AdminAccess;
use App\MoonShine\Support\AdminNavigationCatalog;
use MoonShine\AssetManager\Css;
use MoonShine\ColorManager\ColorManager;
use MoonShine\ColorManager\Palettes\CyanPalette;
use MoonShine\Contracts\ColorManager\ColorManagerContract;
use MoonShine\Contracts\ColorManager\PaletteContract;
use MoonShine\Contracts\MenuManager\MenuElementContract;
use MoonShine\Laravel\Layouts\AppLayout;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\MenuManager\MenuGroup;
use MoonShine\MenuManager\MenuItem;

final class MoonShineLayout extends AppLayout
{
    /**
     * @var null|class-string<PaletteContract>
     */
    protected ?string $palette = CyanPalette::class;

    protected function assets(): array
    {
        return [
            ...parent::assets(),
            Css::make('/admin/moonshine.css?v=3'),
        ];
    }

    protected function menu(): array
    {
        $access = app(AdminAccess::class);
        $user = auth('moonshine')->user();
        $moonShineUser = $user instanceof MoonshineUser ? $user : null;

        $groups = [
            MenuItem::make(
                Dashboard::class,
                static fn (): string => __('admin_panel.navigation.overview'),
                'home',
            )->canSee(static fn (): bool => $access->role($moonShineUser) !== null),
        ];

        foreach (AdminNavigationCatalog::menuGroups() as $group) {
            $items = $this->menuItems($group['resources'], $access, $moonShineUser);
            if ($items === []) {
                continue;
            }

            $groups[] = MenuGroup::make(
                $group['title'],
                $items,
                $group['icon'],
            );
        }

        return $groups;
    }

    /**
     * @param  array<int, class-string>  $resources
     * @return list<MenuElementContract>
     */
    private function menuItems(
        array $resources,
        AdminAccess $access,
        ?MoonshineUser $user,
    ): array {
        $items = [];

        foreach ($resources as $resourceClass) {
            if (! $access->canViewResource($user, $resourceClass)) {
                continue;
            }

            $items[] = MenuItem::make($resourceClass);
        }

        return $items;
    }

    /**
     * @param  ColorManager  $colorManager
     */
    protected function colors(ColorManagerContract $colorManager): void
    {
        parent::colors($colorManager);

        $colorManager
            ->primary('#0891b2')
            ->primary('#22d3ee', dark: true);
    }
}
