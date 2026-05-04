<?php

namespace App\Modules\Shifr\Actions\Toolkit;

use App\Modules\Shifr\DTO\Toolkit\TransformLookupDTO;

final class TransformTextAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(TransformLookupDTO $dto): array
    {
        $operation = strtolower($dto->operation);

        $result = match ($operation) {
            'base64_encode' => base64_encode($dto->input),
            'base64_decode' => $this->safeBase64Decode($dto->input),
            'hex_encode' => bin2hex($dto->input),
            'hex_decode' => $this->safeHexDecode($dto->input),
            'url_encode' => rawurlencode($dto->input),
            'url_decode' => rawurldecode($dto->input),
            default => $dto->input,
        };

        return [
            'operation' => $operation,
            'inputLength' => mb_strlen($dto->input),
            'outputLength' => mb_strlen($result),
            'value' => $result,
        ];
    }

    private function safeBase64Decode(string $input): string
    {
        $decoded = base64_decode($input, true);

        return $decoded === false ? '' : $decoded;
    }

    private function safeHexDecode(string $input): string
    {
        $trimmed = trim($input);
        if ($trimmed === '' || (strlen($trimmed) % 2) !== 0 || !preg_match('/^[a-fA-F0-9]+$/', $trimmed)) {
            return '';
        }

        $decoded = hex2bin($trimmed);

        return $decoded === false ? '' : $decoded;
    }
}
