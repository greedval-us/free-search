<?php

namespace App\Services\Dashboard\Contracts;

use App\Models\User;

interface UserDashboardServiceInterface
{
    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function build(User $user, array $filters = []): array;
}

