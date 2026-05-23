<?php

namespace App\Modules\Shifr\DTO\Contracts;

interface ShifrResultDataInterface
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}

