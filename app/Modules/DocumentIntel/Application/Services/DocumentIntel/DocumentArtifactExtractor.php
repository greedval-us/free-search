<?php

namespace App\Modules\DocumentIntel\Application\Services\DocumentIntel;

use App\Modules\DocumentIntel\Application\Support\DocumentIntelConfig;

final class DocumentArtifactExtractor
{
    public function __construct(private readonly DocumentIntelConfig $config)
    {
    }

    /**
     * @return array{emails: array<int, string>, usernames: array<int, string>, paths: array<int, string>}
     */
    public function extract(string $text): array
    {
        if ($text === '') {
            return [
                'emails' => [],
                'usernames' => [],
                'paths' => [],
            ];
        }

        $emails = $this->extractByPattern($text, '/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', 40);
        $usernames = $this->extractByPattern($text, '/@[a-z0-9_]{3,32}/i', 40);
        $paths = array_merge(
            $this->extractByPattern($text, '/[A-Z]:\\\\[^\s\"\'\'<>|]{3,}/i', 40),
            $this->extractByPattern($text, '/\/(home|var|etc|srv|opt|usr)\/[a-z0-9_\-\/\.]{3,}/i', 40)
        );

        return [
            'emails' => $this->limitUnique($emails),
            'usernames' => $this->limitUnique($usernames),
            'paths' => $this->limitUnique($paths),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function extractByPattern(string $text, string $pattern, int $limit): array
    {
        preg_match_all($pattern, $text, $matches);

        $values = [];
        foreach ($matches[0] ?? [] as $value) {
            $trimmed = trim((string) $value);
            if ($trimmed !== '') {
                $values[] = $trimmed;
            }

            if (count($values) >= $limit) {
                break;
            }
        }

        return $values;
    }

    /**
     * @param array<int, string> $values
     * @return array<int, string>
     */
    private function limitUnique(array $values): array
    {
        $maxItems = $this->config->extractionMaxItemsPerType();

        return array_slice(array_values(array_unique($values)), 0, max(1, $maxItems));
    }
}
