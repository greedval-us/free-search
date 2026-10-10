<?php

namespace App\Modules\SiteIntel\Infrastructure\Clients;

use App\Exceptions\Public\ExternalServiceUnavailableException;
use App\Modules\SiteIntel\Application\Support\SiteIntelConfig;
use App\Modules\SiteIntel\Support\ResolvedSiteIntelTarget;
use GuzzleHttp\Psr7\Utils as StreamUtils;
use GuzzleHttp\Utils;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class SiteIntelHttpClient
{
    public function __construct(
        private readonly SiteIntelConfig $config,
        private readonly SiteIntelHttpRequestOptions $requestOptions,
        private readonly SiteIntelBoundedTransport $transport,
    ) {}

    /** @param array<string, string> $headers */
    public function get(ResolvedSiteIntelTarget $target, array $headers, bool $verifySsl): Response
    {
        $limit = $this->config->httpMaxResponseBytes();
        $response = Http::withHeaders($headers)
            ->withOptions($this->requestOptions->build($target, $verifySsl))
            ->timeout($this->config->httpTimeoutSeconds())
            // Install below Laravel's stubs so fakes keep their normal contract.
            ->setHandler($this->transport->wrap(Utils::chooseHandler(), $limit))
            ->get($target->url);

        $stream = $response->toPsrResponse()->getBody();

        try {
            if ($stream->isSeekable()) {
                $stream->rewind();
            }

            $body = '';
            while (! $stream->eof()) {
                // Also bound alternate handlers/fakes without casting the entire stream.
                $chunk = $stream->read(min(8192, $limit - strlen($body) + 1));
                if (strlen($chunk) > $limit - strlen($body)) {
                    throw new ExternalServiceUnavailableException(
                        'errors.api.site_intel.response_too_large',
                        'site_intel_response_too_large',
                    );
                }
                if ($chunk === '' && ! $stream->eof()) {
                    throw new ConnectionException('Unable to finish reading the Site Intel response.');
                }
                $body .= $chunk;
            }

            return new Response($response->toPsrResponse()->withBody(StreamUtils::streamFor($body)));
        } finally {
            $stream->close();
        }
    }
}
