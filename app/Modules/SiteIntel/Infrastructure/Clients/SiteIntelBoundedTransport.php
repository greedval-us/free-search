<?php

namespace App\Modules\SiteIntel\Infrastructure\Clients;

use App\Exceptions\Public\ExternalServiceUnavailableException;
use Closure;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Throwable;

final class SiteIntelBoundedTransport
{
    public function wrap(callable $handler, int $maxBytes): Closure
    {
        return static function (RequestInterface $request, array $options) use ($handler, $maxBytes): PromiseInterface {
            $sink = new SiteIntelBoundedResponseStream($maxBytes);
            // Buffer only through this sink. cURL/StreamHandler write decoded bytes here.
            $options['sink'] = $sink;
            $options['stream'] = false;
            $options['decode_content'] = true;

            try {
                $promise = $handler($request, $options);
            } catch (Throwable $exception) {
                $failure = self::failure($sink, $exception);
                $sink->close();

                throw $failure;
            }

            return $promise->then(null, static function (mixed $reason) use ($sink): never {
                $failure = self::failure($sink, Create::exceptionFor($reason));
                $sink->close();

                throw $failure;
            });
        };
    }

    private static function failure(SiteIntelBoundedResponseStream $sink, Throwable $exception): Throwable
    {
        return $sink->limitExceeded()
            ? new ExternalServiceUnavailableException(
                'errors.api.site_intel.response_too_large',
                'site_intel_response_too_large',
                $exception,
            )
            : $exception;
    }
}
