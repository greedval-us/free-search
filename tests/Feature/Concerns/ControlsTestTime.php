<?php

namespace Tests\Feature\Concerns;

use Carbon\Carbon;
use Carbon\CarbonImmutable;

trait ControlsTestTime
{
    protected function tearDownControlsTestTime(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
    }

    private function freezeNow(string $datetime): void
    {
        $moment = Carbon::parse($datetime, config('app.timezone'));
        Carbon::setTestNow($moment);
        CarbonImmutable::setTestNow(CarbonImmutable::instance($moment));
    }
}
