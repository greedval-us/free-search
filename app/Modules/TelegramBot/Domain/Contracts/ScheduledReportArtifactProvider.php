<?php

namespace App\Modules\TelegramBot\Domain\Contracts;

use App\Models\User;

interface ScheduledReportArtifactProvider extends ArtifactProvider
{
    public function allows(User $user): bool;

    public function allowsDelivery(User $user, int $reportId, bool $automatic): bool;

    /** Owner of a completed report whose current schedule permits automatic delivery. */
    public function automaticRecipient(int $reportId): ?int;
}
