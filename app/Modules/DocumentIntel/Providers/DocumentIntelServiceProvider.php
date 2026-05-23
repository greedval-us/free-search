<?php

namespace App\Modules\DocumentIntel\Providers;

use App\Modules\DocumentIntel\Application\Contracts\DocumentMetadataExtractorInterface;
use App\Modules\DocumentIntel\Application\Contracts\DocumentUrlCollectorInterface;
use App\Modules\DocumentIntel\Infrastructure\Clients\DocumentMetadataExtractor;
use App\Modules\DocumentIntel\Infrastructure\Clients\DocumentUrlCollector;
use Illuminate\Support\ServiceProvider;

final class DocumentIntelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DocumentUrlCollectorInterface::class, DocumentUrlCollector::class);
        $this->app->bind(DocumentMetadataExtractorInterface::class, DocumentMetadataExtractor::class);
    }
}

