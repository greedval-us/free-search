<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\UserModulePin;
use App\Support\Dashboard\DashboardModuleRegistry;
use Illuminate\Support\Facades\Schema;

class ModulePinService
{
    public function __construct(
        private readonly DashboardModuleRegistry $moduleRegistry,
    ) {
    }

    public function toggle(User $user, string $moduleKey): void
    {
        if (!$this->moduleRegistry->isSupported($moduleKey) || !Schema::hasTable('user_module_pins')) {
            return;
        }

        $existing = UserModulePin::query()
            ->where('user_id', $user->id)
            ->where('module_key', $moduleKey)
            ->first();

        if ($existing instanceof UserModulePin) {
            $existing->delete();

            return;
        }

        UserModulePin::query()->create([
            'user_id' => $user->id,
            'module_key' => $moduleKey,
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function listForUser(User $user): array
    {
        if (!Schema::hasTable('user_module_pins')) {
            return [];
        }

        return UserModulePin::query()
            ->where('user_id', $user->id)
            ->orderBy('created_at')
            ->pluck('module_key')
            ->filter(fn ($value): bool => is_string($value) && $value !== '')
            ->values()
            ->all();
    }
}

