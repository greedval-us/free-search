<?php

namespace App\Modules\ParserSupport\Contracts;

use App\Models\ParserRun;

interface ParserRunHistoryPresenterInterface
{
    /**
     * @param  array<string, mixed>|null  $run
     * @return array<string, mixed>
     */
    public function present(ParserRun $metadata, ?array $run): array;
}
