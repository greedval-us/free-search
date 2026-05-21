<?php

namespace App\Modules\YouTube\Parser\Contracts;

use App\Modules\YouTube\DTO\Request\YouTubeCommentsQueryDTO;

interface YouTubeParserApplicationServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function comments(YouTubeCommentsQueryDTO $query): array;
}
