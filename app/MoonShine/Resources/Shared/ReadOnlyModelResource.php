<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Shared;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;

abstract class ReadOnlyModelResource extends ModelResource
{
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
}
