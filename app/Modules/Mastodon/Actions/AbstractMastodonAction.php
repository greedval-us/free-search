<?php

namespace App\Modules\Mastodon\Actions;

use App\Modules\Mastodon\Core\Contracts\MastodonGatewayInterface;

abstract class AbstractMastodonAction
{
    public function __construct(protected readonly MastodonGatewayInterface $gateway) {}
}
