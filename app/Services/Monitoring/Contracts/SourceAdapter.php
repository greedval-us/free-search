<?php

namespace App\Services\Monitoring\Contracts;

use App\Models\MonitoringSource;
use App\Services\Monitoring\CollectionPage;
use Carbon\CarbonImmutable;

interface SourceAdapter
{
    /** @return array{identity: string, title: string, configuration: array} */
    public function resolve(string $input): array;

    public function fetch(MonitoringSource $source, CarbonImmutable $from, CarbonImmutable $until, ?string $cursor): CollectionPage;
}
