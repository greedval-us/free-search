<?php

namespace App\Modules\Bluesky\Actions;

use App\Modules\Bluesky\Core\Contracts\BlueskyGatewayInterface;
use Illuminate\Support\Arr;
use RuntimeException;

abstract class AbstractBlueskyAction
{
    public function __construct(protected readonly BlueskyGatewayInterface $gateway) {}

    protected function listPayload(array $payload, string $key, string $identifier): array
    {
        if (! is_array($payload[$key] ?? null) || ! array_is_list($payload[$key])
            || (isset($payload['cursor']) && ! is_string($payload['cursor']))) {
            throw new RuntimeException('Bluesky returned an invalid page.');
        }
        foreach ($payload[$key] as $item) {
            $id = is_array($item) ? Arr::get($item, $identifier) : null;
            if (! is_string($id) || trim($id) === '') {
                throw new RuntimeException('Bluesky returned an invalid page item.');
            }
        }

        return $payload[$key];
    }
}
