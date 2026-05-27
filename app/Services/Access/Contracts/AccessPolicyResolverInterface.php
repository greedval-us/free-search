<?php

namespace App\Services\Access\Contracts;

use App\Models\User;
use App\Services\Access\AccessResourcePolicy;

interface AccessPolicyResolverInterface
{
    public function routePolicy(string $routeName): ?AccessResourcePolicy;

    public function resourcePolicy(string $resource, bool $counts = true): AccessResourcePolicy;

    public function canBypass(User $user): bool;
}
