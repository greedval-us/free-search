<?php

namespace App\Services\Access\Contracts;

use App\Models\User;
use App\Services\Access\DTO\FeatureAccessDecision;

interface FeatureAccessServiceInterface
{
    public function consume(User $user, string $routeName): FeatureAccessDecision;

    public function inspect(User $user, string $resource, bool $counts = true): FeatureAccessDecision;
}
