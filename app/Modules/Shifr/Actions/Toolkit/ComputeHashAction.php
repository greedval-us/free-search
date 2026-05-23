<?php

namespace App\Modules\Shifr\Actions\Toolkit;

use App\Modules\Shifr\DTO\Toolkit\HashLookupDTO;
use App\Modules\Shifr\DTO\Toolkit\Results\HashResultDTO;
use App\Modules\Shifr\Support\HashAlgorithms;

final class ComputeHashAction
{

    public function execute(HashLookupDTO $dto): HashResultDTO
    {
        $algorithm = strtolower($dto->algorithm);

        $algorithm = $this->resolveAlgorithm($algorithm, $dto->hmacKey !== null && $dto->hmacKey !== '');

        $hash = $dto->hmacKey !== null && $dto->hmacKey !== ''
            ? hash_hmac($algorithm, $dto->input, $dto->hmacKey)
            : hash($algorithm, $dto->input);

        return new HashResultDTO(
            algorithm: $algorithm,
            mode: $dto->hmacKey !== null && $dto->hmacKey !== '' ? 'hmac' : 'hash',
            value: $hash,
            inputLength: mb_strlen($dto->input),
            analysis: $this->analyzeHashLike($dto->input),
        );
    }

    private function resolveAlgorithm(string $algorithm, bool $isHmac): string
    {
        if (!in_array($algorithm, HashAlgorithms::ALL, true)) {
            return HashAlgorithms::DEFAULT;
        }

        $available = array_map('strtolower', hash_algos());
        if (!in_array($algorithm, $available, true)) {
            return HashAlgorithms::DEFAULT;
        }

        if (!$isHmac) {
            return $algorithm;
        }

        if (function_exists('hash_hmac_algos')) {
            $hmacAvailable = array_map('strtolower', hash_hmac_algos());
            if (!in_array($algorithm, $hmacAvailable, true)) {
                return HashAlgorithms::DEFAULT;
            }
        }

        return $algorithm;
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
