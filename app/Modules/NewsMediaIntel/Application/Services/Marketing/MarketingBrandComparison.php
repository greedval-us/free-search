<?php

namespace App\Modules\NewsMediaIntel\Application\Services\Marketing;

use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel\NewsSentimentAnalyzer;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsMarketingLookupDTO;

final readonly class MarketingBrandComparison
{
    public function __construct(private NewsSentimentAnalyzer $sentiment) {}

    public function build(NewsMarketingLookupDTO $lookup, array $mentions): array
    {
        $names = [];
        foreach (['brand' => [$lookup->brand], 'competitor' => array_slice($lookup->competitors, 0, 3)] as $kind => $values) {
            foreach ($values as $name) {
                $name = trim($name);
                if ($name !== '' && ! isset($names[mb_strtolower($name)])) {
                    $names[mb_strtolower($name)] = ['name' => $name, 'kind' => $kind];
                }
            }
        }

        $entities = [];
        $documentMatches = [];
        $total = 0;
        foreach ($names as $entity) {
            $matched = [];
            $pattern = '/(?<![\p{L}\p{N}_])'.preg_quote($entity['name'], '/').'(?![\p{L}\p{N}_])/iu';
            foreach ($mentions as $index => $mention) {
                if (preg_match($pattern, $mention->title.' '.$mention->snippet) === 1) {
                    $matched[] = $mention;
                    $documentMatches[$index] = ($documentMatches[$index] ?? 0) + 1;
                }
            }
            $total += count($matched);
            $entities[] = [...$entity, 'mentions' => count($matched),
                'sentiment' => $this->sentiment->summarize($matched)->toArray(),
                'evidenceUrls' => array_slice(array_values(array_unique(array_map(static fn ($mention): string => $mention->link, $matched))), 0, 5)];
        }
        foreach ($entities as &$entity) {
            $entity['share'] = MarketingMentionSet::percent($entity['mentions'], $total);
        }
        unset($entity);

        return ['denominator' => 'entity_matches', 'totalMatches' => $total, 'entities' => $entities,
            'coMentionDocuments' => count(array_filter($documentMatches, static fn (int $count): bool => $count > 1))];
    }
}
