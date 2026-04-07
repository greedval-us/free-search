<?php

namespace App\Providers;

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class MadelineProtoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(API::class, function (): API {
            $apiId   = env('TELEGRAM_API_ID');
            $apiHash = env('TELEGRAM_API_HASH');

            if (empty($apiId)) {
                throw new \RuntimeException('TELEGRAM_API_ID is not set in .env');
            }

            if (empty($apiHash)) {
                throw new \RuntimeException('TELEGRAM_API_HASH is not set in .env');
            }

            $sessionPath = Config::get('madelineproto.session_path', 'app/madelineproto/');
            $sessionPath = storage_path("{$sessionPath}session.madeline");

            $settings = (new Settings)
                ->setAppInfo(
                    (new AppInfo)
                        ->setApiId($apiId)
                        ->setApiHash($apiHash)
                );

            return new API($sessionPath, $settings);
        });
    }

    public function boot(): void
    {
        //
    }
}
