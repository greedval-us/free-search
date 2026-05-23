<?php

namespace App\Support\Reports\Contracts;

use Carbon\CarbonInterface;

interface ReportFilenamePolicyInterface
{
    public function build(string $prefix, string $target, ?CarbonInterface $now = null): string;

    public function buildWithExtension(
        string $prefix,
        string $target,
        string $extension,
        ?CarbonInterface $now = null,
    ): string;
}

