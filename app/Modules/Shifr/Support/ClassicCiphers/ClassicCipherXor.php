<?php

namespace App\Modules\Shifr\Support\ClassicCiphers;

final class ClassicCipherXor
{
    public function encrypt(string $message, string $key): string
    {
        if ($key === '' || $message === '') {
            return $message;
        }

        return base64_encode($this->xorBytes($message, $key));
    }

    public function decrypt(string $message, string $key): string
    {
        if ($key === '' || $message === '') {
            return $message;
        }

        $decoded = base64_decode($message, true);
        if ($decoded === false) {
            return $message;
        }

        return $this->xorBytes($decoded, $key);
    }

    private function xorBytes(string $input, string $key): string
    {
        $keyBytes = array_values(unpack('C*', $key) ?: []);
        $inputBytes = array_values(unpack('C*', $input) ?: []);
        $keyLength = count($keyBytes);

        if ($keyLength === 0) {
            return $input;
        }

        foreach ($inputBytes as $index => $byte) {
            $inputBytes[$index] = $byte ^ $keyBytes[$index % $keyLength];
        }

        return pack('C*', ...$inputBytes) ?: '';
    }
}
