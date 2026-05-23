<?php

namespace App\Modules\DocumentIntel\Providers;

use App\Modules\DocumentIntel\Application\Contracts\DocumentMetadataExtractorInterface;
use App\Modules\DocumentIntel\Application\Contracts\DocumentIntelServiceInterface;
use App\Modules\DocumentIntel\Application\Contracts\DocumentUrlCollectorInterface;
use App\Modules\DocumentIntel\Application\Services\DocumentIntelService;
use App\Modules\DocumentIntel\Infrastructure\Clients\DocumentMetadataExtractor;
use App\Modules\DocumentIntel\Infrastructure\Clients\DocumentUrlCollector;
use App\Support\Providers\BindingsServiceProvider;

final class DocumentIntelServiceProvider extends BindingsServiceProvider
{
    protected function bindings(): array
    {
        return [
            DocumentUrlCollectorInterface::class => DocumentUrlCollector::class,
            DocumentMetadataExtractorInterface::class => DocumentMetadataExtractor::class,
            DocumentIntelServiceInterface::class => DocumentIntelService::class,
        ];
    }
}
