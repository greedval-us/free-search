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
            'base64url_encode' => $this->base64UrlEncode($dto->input),
            'base64url_decode' => $this->base64UrlDecode($dto->input),
            'hex_encode' => bin2hex($dto->input),
            'hex_decode' => $this->safeHexDecode($dto->input),
            'url_encode' => rawurlencode($dto->input),
            'url_decode' => rawurldecode($dto->input),
            'html_encode' => htmlspecialchars($dto->input, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'html_decode' => html_entity_decode($dto->input, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
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

    private function base64UrlEncode(string $input): string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $input): string
    {
        $normalized = strtr(trim($input), '-_', '+/');
        $padding = strlen($normalized) % 4;
        if ($padding > 0) {
            $normalized .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($normalized, true);

        return $decoded === false ? '' : $decoded;
    }
}
