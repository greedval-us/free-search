<?php

namespace Tests\Unit;

use App\Modules\ParserSupport\PaginationCursorHistory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PaginationCursorHistoryTest extends TestCase
{
    public function test_token_history_preserves_opaque_values_and_discards_invalid_entries(): void
    {
        $history = PaginationCursorHistory::fromTokens(['opaque+one=', 'opaque+two=', 'opaque+one=', '', 12, null]);

        $this->assertSame(['opaque+one=', 'opaque+two='], $history->tokens());
        $this->assertTrue($history->contains('opaque+one='));
        $this->assertFalse($history->contains('opaque+three='));
        $this->assertSame([
            hash('sha256', 'opaque+one=') => true,
            hash('sha256', 'opaque+two=') => true,
        ], $history->hashes());
    }

    public function test_hash_history_remains_hashed_after_rehydration(): void
    {
        $hash = hash('sha256', 'private-opaque-token');
        $history = PaginationCursorHistory::fromHashes([$hash => true, 'malformed' => true, hash('sha256', 'missing') => null]);

        $this->assertTrue($history->contains('private-opaque-token'));
        $this->assertFalse($history->contains('missing'));
        $this->assertSame([$hash => true], $history->hashes());
        $this->assertSame([], $history->tokens());
        $this->assertSame([$hash => true], PaginationCursorHistory::fromHashes($history->hashes())->hashes());
    }

    #[DataProvider('nonAdvancingPages')]
    public function test_repeat_or_cycle_is_rejected_without_exposing_or_mutating_the_cursor(string $current, string $next): void
    {
        $history = PaginationCursorHistory::fromTokens(['private-older-token']);
        $before = $history->hashes();

        try {
            $history->assertAdvances($current, $next, 'Pagination did not advance.');
            $this->fail('A repeated cursor must fail explicitly.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Pagination did not advance.', $exception->getMessage());
        }

        $this->assertSame($before, $history->hashes());
    }

    public static function nonAdvancingPages(): array
    {
        return [
            'same cursor' => ['private-current-token', 'private-current-token'],
            'earlier cursor' => ['private-current-token', 'private-older-token'],
        ];
    }

    public function test_unseen_and_terminal_pages_are_allowed_without_inventing_a_cursor(): void
    {
        $history = PaginationCursorHistory::fromTokens(['earlier']);

        $history->assertAdvances('current', 'next', 'Pagination did not advance.');
        $history->remember('current');
        $history->assertAdvances('current', null, 'Pagination did not advance.');
        $history->remember(null);
        $history->remember('');

        $this->assertSame(['earlier', 'current'], $history->tokens());
        $this->assertFalse($history->contains('next'));
    }

    public function test_missing_legacy_history_is_an_empty_history(): void
    {
        $this->assertSame([], PaginationCursorHistory::fromTokens(null)->tokens());
        $this->assertSame([], PaginationCursorHistory::fromHashes('invalid')->hashes());
    }
}
