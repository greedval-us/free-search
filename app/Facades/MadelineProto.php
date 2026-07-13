<?php
namespace App\Facades;

use App\Support\MadelineProto\MadelineProtoManager;
use Illuminate\Support\Facades\Facade;

class MadelineProto extends Facade
{
    protected static function getFacadeAccessor()
    {
        return MadelineProtoManager::class;
    }
}
