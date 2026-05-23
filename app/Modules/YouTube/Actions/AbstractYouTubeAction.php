<?php

namespace App\Modules\YouTube\Actions;

use App\Modules\YouTube\Core\Contracts\YouTubeGatewayInterface;

abstract class AbstractYouTubeAction
{
    public function __construct(protected readonly YouTubeGatewayInterface $gateway) {}
}
