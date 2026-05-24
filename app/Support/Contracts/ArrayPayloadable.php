<?php

namespace App\Support\Contracts;

interface ArrayPayloadable
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}

