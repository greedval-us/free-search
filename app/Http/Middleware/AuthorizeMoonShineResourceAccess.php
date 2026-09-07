<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\MoonShine\Support\AdminAccess;
use Closure;
use Illuminate\Http\Request;
use MoonShine\Contracts\Core\CrudResourceContract;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Support\Enums\Ability;
use MoonShine\Support\Enums\PageType;
use Symfony\Component\HttpFoundation\Response;

final readonly class AuthorizeMoonShineResourceAccess
{
    public function __construct(
        private AdminAccess $access,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('moonshine')->user();
        $resource = moonshineRequest()->getResource();

        if ($user instanceof MoonshineUser) {
            abort_if($this->access->role($user) === null, Response::HTTP_FORBIDDEN);
        }

        if ($user instanceof MoonshineUser && $resource instanceof CrudResourceContract) {
            abort_unless(
                $this->access->allows($user, $resource::class, $this->ability($request, $resource)),
                Response::HTTP_FORBIDDEN,
            );
        }

        return $next($request);
    }

    private function ability(Request $request, CrudResourceContract $resource): Ability
    {
        return match ($request->route()?->getName()) {
            'moonshine.crud.create', 'moonshine.crud.store' => Ability::CREATE,
            'moonshine.crud.edit', 'moonshine.crud.update',
            'moonshine.update-field.through-column', 'moonshine.update-field.through-relation' => Ability::UPDATE,
            'moonshine.crud.destroy' => Ability::DELETE,
            'moonshine.crud.massDelete' => Ability::MASS_DELETE,
            'moonshine.crud.show' => Ability::VIEW,
            'moonshine.resource.page' => $this->pageAbility($resource),
            default => Ability::VIEW_ANY,
        };
    }

    private function pageAbility(CrudResourceContract $resource): Ability
    {
        return match ($resource->getActivePage()?->getPageType()) {
            PageType::DETAIL => Ability::VIEW,
            PageType::FORM => $resource->getItemID() === null ? Ability::CREATE : Ability::UPDATE,
            default => Ability::VIEW_ANY,
        };
    }
}
