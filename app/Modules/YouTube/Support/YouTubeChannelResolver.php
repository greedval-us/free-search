<?php

namespace App\Modules\YouTube\Support;

use App\Exceptions\Public\ExternalServiceUnavailableException;
use App\Exceptions\Public\PublicResourceNotFoundException;
use App\Modules\YouTube\Core\Contracts\YouTubeGatewayInterface;
use Illuminate\Support\Arr;

class YouTubeChannelResolver
{
    public function __construct(
        private readonly YouTubeGatewayInterface $gateway,
        private readonly YouTubeChannelInputNormalizer $inputNormalizer,
    ) {}

    public function resolve(string $channelInput): string
    {
        $channelInput = trim($channelInput);

        if ($channelInput === '') {
            return '';
        }

        if ($this->inputNormalizer->looksLikeChannelId($channelInput)) {
            return $channelInput;
        }

        $byHandle = $this->firstChannelId([
            'forHandle' => $this->inputNormalizer->normalizeHandle($channelInput),
        ]);

        if ($byHandle !== null) {
            return $byHandle;
        }

        $byUsername = $this->firstChannelId([
            'forUsername' => $this->inputNormalizer->normalizeUsername($channelInput),
        ]);

        if ($byUsername !== null) {
            return $byUsername;
        }

        throw new PublicResourceNotFoundException('errors.api.youtube.channel_not_found', 'youtube_channel_not_found');
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function firstChannelId(array $params): ?string
    {
        if (trim((string) reset($params)) === '') {
            return null;
        }

        $payload = $this->gateway->channels([
            ...$params,
            'part' => 'id',
            'maxResults' => 1,
        ]);

        $items = $payload['items'] ?? null;
        if (isset($payload['error']) || ! is_array($items) || ! array_is_list($items)) {
            throw new ExternalServiceUnavailableException('errors.api.youtube.request_failed', 'youtube_analytics_invalid_response');
        }
        if ($items === []) {
            return null;
        }

        $id = Arr::get($payload, 'items.0.id');

        if (! is_string($id) || ! $this->inputNormalizer->looksLikeChannelId($id)) {
            throw new ExternalServiceUnavailableException('errors.api.youtube.request_failed', 'youtube_analytics_invalid_response');
        }

        return $id;
    }
}
