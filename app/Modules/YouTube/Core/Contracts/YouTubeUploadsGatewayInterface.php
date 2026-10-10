<?php

namespace App\Modules\YouTube\Core\Contracts;

interface YouTubeUploadsGatewayInterface
{
    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function playlistItems(array $params): array;
}
