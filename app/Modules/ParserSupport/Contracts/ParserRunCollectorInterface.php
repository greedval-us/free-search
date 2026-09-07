<?php

namespace App\Modules\ParserSupport\Contracts;

interface ParserRunCollectorInterface
{
    /**
     * @param  array<string, mixed>  $run
     * @return array<string, mixed>
     */
    public function advance(array $run): array;

    /**
     * @param  array<string, mixed>  $run
     * @return array<string, mixed>
     */
    public function buildResultSnapshot(array $run): array;
}
