<?php

namespace App\Modules\Bluesky\Actions;

use App\Modules\Bluesky\Core\Contracts\BlueskyGatewayInterface;

abstract class AbstractBlueskyAction
{
    public function __construct(protected readonly BlueskyGatewayInterface $gateway) {}
}
