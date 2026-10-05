<?php

namespace App\Modules\ParserSupport;

use RuntimeException;

final class PaginationCursorHistory
{
    /** @var array<string, string|null> */
    private array $seen = [];

    public static function fromTokens(mixed $tokens): self
    {
        $history = new self;
        foreach (is_array($tokens) ? $tokens : [] as $token) {
            if (is_string($token)) {
                $history->remember($token);
            }
        }

        return $history;
    }

    public static function fromHashes(mixed $hashes): self
    {
        $history = new self;
        foreach (is_array($hashes) ? $hashes : [] as $hash => $value) {
            if (is_string($hash) && preg_match('/^[a-f0-9]{64}$/D', $hash) === 1 && $value !== null) {
                $history->seen[$hash] = null;
            }
        }

        return $history;
    }

    public function assertAdvances(?string $current, ?string $next, string $message): void
    {
        if ($next !== null && ($next === $current || $this->contains($next))) {
            throw new RuntimeException($message);
        }
    }

    public function contains(string $cursor): bool
    {
        return array_key_exists(hash('sha256', $cursor), $this->seen);
    }

    public function remember(?string $cursor): void
    {
        if ($cursor !== null && $cursor !== '') {
            $this->seen[hash('sha256', $cursor)] = $cursor;
        }
    }

    /** @return array<int, string> */
    public function tokens(): array
    {
        return array_values(array_filter($this->seen, static fn (?string $token): bool => $token !== null));
    }

    /** @return array<string, bool> */
    public function hashes(): array
    {
        return array_fill_keys(array_keys($this->seen), true);
    }
}
