<?php

namespace App\Modules\YouTube\Core\Contracts;

interface YouTubeGatewayInterface
{
    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function searchVideos(array $params): array;

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function analyticsSummary(array $params): array;

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function videoComments(array $params): array;
}
