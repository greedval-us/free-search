<?php

namespace App\Modules\Gdelt\Infrastructure;

use App\Modules\Gdelt\Core\Contracts\GdeltGatewayInterface;
use App\Modules\Gdelt\DTO\Request\GdeltSearchQueryDTO;
use Illuminate\Support\Facades\Http;

class GdeltHttpGateway implements GdeltGatewayInterface
{
    /**
     * @return array<string, mixed>
     */
    public function searchArticles(GdeltSearchQueryDTO $query): array
    {
        $url = (string) config('services.gdelt.base_url', 'https://api.gdeltproject.org/api/v2/doc/doc');
        $timeout = (int) config('services.gdelt.timeout', 15);

        $response = Http::acceptJson()
            ->timeout(max(3, $timeout))
            ->retry(2, 300, throw: false)
            ->get($url, $query->toApiParams());

        if (!$response->ok()) {
            throw new \RuntimeException('GDELT API request failed with status ' . $response->status());
        }

        $payload = $response->json();
        if (!is_array($payload)) {
            throw new \RuntimeException('GDELT API returned an invalid payload.');
        }

        return $payload;
    }
}
