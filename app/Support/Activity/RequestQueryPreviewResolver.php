<?php

namespace App\Support\Activity;

use Illuminate\Support\Str;

class RequestQueryPreviewResolver
{
    /**
     * @var array<int, string>
     */
    private array $previewKeys = [
        'query',
        'q',
        'username',
        'email',
        'domain',
        'target',
        'url',
        'full_name',
        'keyword',
        'fio',
        'channel',
        'channelUsername',
        'chatUsername',
    ];

    /**
     * @param array<string, mixed> $payload
     */
    public function resolve(array $payload): ?string
    {
        foreach ($this->previewKeys as $key) {
            $value = $payload[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return Str::limit(trim($value), 120);
            }
        }

        return null;
    }
}

