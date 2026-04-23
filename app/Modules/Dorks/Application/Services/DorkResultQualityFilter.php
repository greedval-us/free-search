<?php

namespace App\Modules\Dorks\Application\Services;

use App\Modules\Dorks\Application\DTO\DorkSearchQueryDTO;
use App\Modules\Dorks\Domain\DTO\DorkResultItemDTO;

final class DorkResultQualityFilter
{
    /**
     * @param array<int, DorkResultItemDTO> $items
     * @return array<int, DorkResultItemDTO>
     */
    public function filter(array $items, DorkSearchQueryDTO $query): array
    {
        $filtered = [];

        foreach ($items as $item) {
            if (!$this->hasSupportedUrl($item->url) || $this->isBlockedDomain($item->domain)) {
                continue;
            }

            $normalized = $this->normalizeItem($item);

            if ($this->looksLikeSpam($normalized)) {
                continue;
            }

            if (!$this->matchesTarget($normalized, $query->target)) {
                continue;
            }

            if (!$this->matchesSite($normalized, $query->site)) {
                continue;
            }

            if (!$this->matchesGoalContext($normalized, $query->goal)) {
                continue;
            }

            $filtered[] = $normalized;
        }

        return $filtered;
    }

    private function hasSupportedUrl(string $url): bool
    {
        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
    }

    private function isBlockedDomain(?string $domain): bool
    {
        if ($domain === null || trim($domain) === '') {
            return false;
        }

        $domain = mb_strtolower(trim($domain));
        $blocked = config('osint.dorks.quality.blocked_domains', []);

        if (!is_array($blocked)) {
            return false;
        }

        return in_array($domain, array_map(static fn ($value): string => mb_strtolower((string) $value), $blocked), true);
    }

    private function normalizeItem(DorkResultItemDTO $item): DorkResultItemDTO
    {
        $title = $this->normalizeText($item->title);
        $snippet = $this->normalizeText($item->snippet);
        $snippet = mb_substr($snippet, 0, $this->snippetMaxLength());

        return new DorkResultItemDTO(
            source: $item->source,
            goal: $item->goal,
            dork: $item->dork,
            title: $title,
            snippet: $snippet,
            url: trim($item->url),
            domain: $item->domain !== null ? mb_strtolower(trim($item->domain)) : null,
        );
    }

    private function normalizeText(string $value): string
    {
        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5);
        $stripped = strip_tags($decoded);
        $normalized = preg_replace('/\s+/u', ' ', trim($stripped));

        return is_string($normalized) ? $normalized : '';
    }

    private function looksLikeSpam(DorkResultItemDTO $item): bool
    {
        $text = mb_strtolower(trim($item->title . ' ' . $item->snippet . ' ' . $item->url));
        if ($text === '') {
            return true;
        }

        if (preg_match('/\b\d{14,}\b/u', $text) === 1) {
            return true;
        }

        foreach ($this->blockedPhrases() as $phrase) {
            if ($phrase !== '' && str_contains($text, $phrase)) {
                return true;
            }
        }

        $tokens = $this->tokenize($text);
        if (count($tokens) < 20) {
            return false;
        }

        $uniqueRatio = count(array_unique($tokens)) / count($tokens);

        return $uniqueRatio < $this->minUniqueTokenRatio();
    }

    private function matchesTarget(DorkResultItemDTO $item, string $target): bool
    {
        $target = mb_strtolower(trim($target));
        if ($target === '') {
            return true;
        }

        $text = mb_strtolower($item->title . ' ' . $item->snippet . ' ' . $item->url);
        if (str_contains($text, $target)) {
            return true;
        }

        $tokens = $this->targetTokens($target);
        if ($tokens === []) {
            return false;
        }

        $matches = 0;
        foreach ($tokens as $token) {
            if (str_contains($text, $token)) {
                $matches++;
            }
        }

        return $matches >= $this->minTargetTokenMatches();
    }

    private function matchesGoalContext(DorkResultItemDTO $item, string $requestedGoal): bool
    {
        $goal = $requestedGoal !== 'all' ? $requestedGoal : $item->goal;
        $goalKeywords = config('osint.dorks.quality.goal_keywords.' . $goal, []);

        if (!is_array($goalKeywords) || $goalKeywords === []) {
            return true;
        }

        $text = mb_strtolower($item->title . ' ' . $item->snippet . ' ' . $item->url);

        foreach ($goalKeywords as $keyword) {
            $keyword = mb_strtolower(trim((string) $keyword));

            if ($keyword !== '' && str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function matchesSite(DorkResultItemDTO $item, ?string $site): bool
    {
        $site = mb_strtolower(trim((string) $site));
        if ($site === '') {
            return true;
        }

        $domain = mb_strtolower(trim((string) $item->domain));
        if ($domain === '') {
            return false;
        }

        return $domain === $site || str_ends_with($domain, '.' . $site);
    }

    /**
     * @return array<int, string>
     */
    private function blockedPhrases(): array
    {
        $phrases = config('osint.dorks.quality.blocked_phrases', []);
        if (!is_array($phrases)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($value): string => mb_strtolower(trim((string) $value)),
            $phrases
        )));
    }

    /**
     * @return array<int, string>
     */
    private function tokenize(string $text): array
    {
        $tokens = preg_split('/[^\p{L}\p{N}_-]+/u', mb_strtolower($text));
        if (!is_array($tokens)) {
            return [];
        }

        return array_values(array_filter($tokens, static fn ($token): bool => mb_strlen((string) $token) >= 3));
    }

    /**
     * @return array<int, string>
     */
    private function targetTokens(string $target): array
    {
        $tokens = $this->tokenize($target);
        $ignored = ['www', 'http', 'https', 'com', 'net', 'org', 'ru', 'de', 'en', 'io'];

        return array_values(array_filter(
            array_unique($tokens),
            static fn (string $token): bool => !in_array($token, $ignored, true)
        ));
    }

    private function snippetMaxLength(): int
    {
        return max(120, (int) config('osint.dorks.quality.snippet_max_length', 320));
    }

    private function minTargetTokenMatches(): int
    {
        return max(1, (int) config('osint.dorks.quality.min_target_token_matches', 1));
    }

    private function minUniqueTokenRatio(): float
    {
        return max(0.1, min(1.0, (float) config('osint.dorks.quality.min_unique_token_ratio', 0.35)));
    }
}
