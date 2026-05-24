<?php

namespace App\Services\Dashboard\Contracts;

use App\Models\User;

interface ModulePinServiceInterface
{
    public function toggle(User $user, string $moduleKey): void;

    /**
     * @return array<int, string>
     */
    public function listForUser(User $user): array;
}

