<?php

namespace App\Providers;

use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Settings\Logger as LoggerSettings;
use App\Support\MadelineProto\MadelineProtoConfig;
use Illuminate\Support\ServiceProvider;

class MadelineProtoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            MadelineProtoConfig::class,
            static fn (): MadelineProtoConfig => MadelineProtoConfig::fromArray(
                (array) config('madelineproto', [])
            )
        );

        $this->app->singleton(API::class, function (): API {
            $config = $this->app->make(MadelineProtoConfig::class);

            $settings = (new Settings)
                ->setAppInfo(
                    (new AppInfo)
                        ->setApiId($config->apiId())
                        ->setApiHash($config->apiHash())
                )
                ->setLogger(
                    (new LoggerSettings)
                        ->setType(Logger::FILE_LOGGER)
                        ->setExtra($config->logFilePath())
                );

            return new API($config->sessionFilePath(), $settings);
        });
    }

    public function boot(): void
    {
        //
    }
}
