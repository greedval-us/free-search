<?php

namespace App\Modules\Shifr\Actions\Toolkit;

use App\Modules\Shifr\DTO\Toolkit\JwtLookupDTO;

final class InspectJwtAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(JwtLookupDTO $dto): array
    {
        $token = trim($dto->token);
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return [
                'validFormat' => false,
                'error' => 'Token must have 3 parts separated by dots.',
            ];
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $headerJson = $this->base64UrlDecode($headerB64);
        $payloadJson = $this->base64UrlDecode($payloadB64);
        $signatureRaw = $this->base64UrlDecode($signatureB64);

        $header = $this->decodeJson($headerJson);
        $payload = $this->decodeJson($payloadJson);

        $now = time();
        $exp = is_array($payload) && isset($payload['exp']) && is_numeric($payload['exp']) ? (int) $payload['exp'] : null;
        $nbf = is_array($payload) && isset($payload['nbf']) && is_numeric($payload['nbf']) ? (int) $payload['nbf'] : null;
        $iat = is_array($payload) && isset($payload['iat']) && is_numeric($payload['iat']) ? (int) $payload['iat'] : null;

        $timeChecks = [
            'now' => $now,
            'issuedAt' => $iat,
            'notBefore' => $nbf,
            'expiresAt' => $exp,
            'isExpired' => $exp !== null ? $now >= $exp : null,
            'isActive' => $nbf !== null ? $now >= $nbf : null,
        ];

        $signature = [
            'present' => $signatureB64 !== '',
            'length' => strlen($signatureRaw),
            'verified' => null,
            'verificationReason' => null,
        ];

        if ($dto->secret !== null && is_array($header)) {
            $alg = (string) ($header['alg'] ?? '');
            $verified = $this->verifyHsSignature($alg, $headerB64 . '.' . $payloadB64, $signatureRaw, $dto->secret);
            $signature['verified'] = $verified['verified'];
            $signature['verificationReason'] = $verified['reason'];
        }

        return [
            'validFormat' => $header !== null && $payload !== null,
            'header' => $header,
            'payload' => $payload,
            'timeChecks' => $timeChecks,
            'signature' => $signature,
        ];
    }

    /**
     * @return array{verified: bool|null, reason: string}
     */
    private function verifyHsSignature(string $alg, string $signingInput, string $signatureRaw, string $secret): array
    {
        $map = [
            'HS256' => 'sha256',
            'HS384' => 'sha384',
            'HS512' => 'sha512',
        ];

        if (!isset($map[$alg])) {
            return ['verified' => null, 'reason' => 'Unsupported or non-HS algorithm.'];
        }

        $expected = hash_hmac($map[$alg], $signingInput, $secret, true);

        return [
            'verified' => hash_equals($expected, $signatureRaw),
            'reason' => 'HMAC signature check performed.',
        ];
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

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJson(string $json): ?array
    {
        if ($json === '') {
            return null;
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }
}
