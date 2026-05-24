<?php

namespace App\Services\Dashboard\Contracts;

use App\Models\User;
use App\Models\UserSavedQuery;
use Illuminate\Database\Eloquent\ModelNotFoundException;

interface SavedQueryServiceInterface
{
    /**
     * @throws ModelNotFoundException
     */
    public function saveFromRequestLog(User $user, int $requestLogId): void;

    public function deleteForUser(User $user, UserSavedQuery $savedQuery): void;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listForUser(User $user, int $limit = 40): array;
}

