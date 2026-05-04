<?php

namespace App\Modules\Shifr\Support;

final class HashAlgorithms
{
    public const DEFAULT = 'sha256';

    public const ALL = [
        'md5',
        'sha1',
        'sha224',
        'sha256',
        'sha384',
        'sha512',
        'sha512/224',
        'sha512/256',
        'sha3-224',
        'sha3-256',
        'sha3-384',
        'sha3-512',
        'blake2s256',
        'blake2b512',
        'ripemd128',
        'ripemd160',
        'ripemd256',
        'ripemd320',
        'whirlpool',
        'xxh32',
        'xxh64',
        'xxh3',
        'xxh128',
        'crc32',
        'crc32b',
        'adler32',
    ];

    public static function validationRuleList(): string
    {
        return implode(',', self::ALL);
    }
}

