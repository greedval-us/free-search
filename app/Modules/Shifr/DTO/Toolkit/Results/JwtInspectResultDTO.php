<?php

namespace App\Modules\Shifr\DTO\Toolkit\Results;

use App\Modules\Shifr\DTO\Contracts\ShifrResultDataInterface;

final class JwtInspectResultDTO implements ShifrResultDataInterface
{
    /**
     * @param array<string, mixed>|null $header
     * @param array<string, mixed>|null $payload
     * @param array<string, mixed> $timeChecks
     * @param array<string, mixed> $signature
     */
    public function __construct(
        public readonly bool $validFormat,
        public readonly ?array $header,
        public readonly ?array $payload,
        public readonly array $timeChecks,
        public readonly array $signature,
        public readonly ?string $error = null,
    ) {
    }

    public static function invalidFormat(string $error): self
    {
        return new self(
            validFormat: false,
            header: null,
            payload: null,
            timeChecks: [],
            signature: [],
            error: $error,
        );
    }

    public function toArray(): array
    {
        $data = [
            'validFormat' => $this->validFormat,
            'header' => $this->header,
            'payload' => $this->payload,
            'timeChecks' => $this->timeChecks,
            'signature' => $this->signature,
        ];

        if ($this->error !== null) {
            $data['error'] = $this->error;
        }

        return $data;
    }
}

