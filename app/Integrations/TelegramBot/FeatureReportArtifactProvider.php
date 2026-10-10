<?php

namespace App\Integrations\TelegramBot;

use App\Models\User;
use App\Modules\TelegramBot\Infrastructure\Artifacts\TemporaryDocuments;
use App\Modules\TelegramBot\Support\BotConfig;
use App\Services\Access\Contracts\FeatureAccessServiceInterface;
use App\Support\Reports\SavedReportRenderer;

abstract readonly class FeatureReportArtifactProvider extends SavedReportArtifactProvider
{
    abstract protected function feature(): string;

    public function __construct(BotConfig $config, TemporaryDocuments $files, SavedReportRenderer $renderer, private FeatureAccessServiceInterface $access)
    {
        parent::__construct($config, $files, $renderer);
    }

    final protected function moduleAllows(User $user): bool
    {
        return $this->access->inspect($user, $this->feature(), false)->allowed;
    }
}
