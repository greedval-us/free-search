<?php

namespace App\Modules\Shifr\Actions\Toolkit;

use App\Modules\Shifr\DTO\Toolkit\HashLookupDTO;

final class ComputeHashAction
{
    private const SUPPORTED = [
        'md5',
        'sha1',
        'sha256',
        'sha512',
    ];

    /**
     * @return array<string, mixed>
     */
    public function execute(HashLookupDTO $dto): array
    {
        $algorithm = strtolower($dto->algorithm);

        if (!in_array($algorithm, self::SUPPORTED, true)) {
            $algorithm = 'sha256';
        }

        $hash = $dto->hmacKey !== null && $dto->hmacKey !== ''
            ? hash_hmac($algorithm, $dto->input, $dto->hmacKey)
            : hash($algorithm, $dto->input);

        return [
            'algorithm' => $algorithm,
            'mode' => $dto->hmacKey !== null && $dto->hmacKey !== '' ? 'hmac' : 'hash',
            'value' => $hash,
            'inputLength' => mb_strlen($dto->input),
            'analysis' => $this->analyzeHashLike($dto->input),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeHashLike(string $input): array
    {
        $trimmed = trim($input);
        $isHex = (bool) preg_match('/^[a-fA-F0-9]+$/', $trimmed);
        $length = strlen($trimmed);

        $guess = null;
        if ($isHex) {
            $guess = match ($length) {
                32 => 'md5',
                40 => 'sha1',
                64 => 'sha256',
                128 => 'sha512',
                default => null,
            };
        }

        return [
            'isHex' => $isHex,
            'length' => $length,
            'possibleAlgorithm' => $guess,
        ];
    }
}
