<?php

namespace App\Modules\NewsMediaIntel\Application\Services\Marketing;

use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel\NewsTopicExtractor;
use App\Modules\NewsMediaIntel\Application\Support\NewsMediaIntelConfig;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsMentionDTO;

final readonly class MarketingContentAnalyzer
{
    public function __construct(private NewsTopicExtractor $topics, private NewsMediaIntelConfig $config) {}

    public function topics(array $mentions): array
    {
        $documents = $tokensByDocument = $phrases = [];
        foreach ($mentions as $index => $mention) {
            $tokens = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($mention->title.' '.$mention->snippet), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $unique = array_values(array_unique($tokens));
            $tokensByDocument[$index] = array_fill_keys($unique, true);
            $documents[] = new NewsMentionDTO(source: $mention->source, title: implode(' ', $unique), snippet: '', link: $mention->link, publishedAt: '');

            $seen = [];
            for ($i = 0; $i + 1 < count($tokens); $i++) {
                $left = $tokens[$i];
                $right = $tokens[$i + 1];
                if ($left === $right || ! $this->usefulWord($left) || ! $this->usefulWord($right)) {
                    continue;
                }
                $phrase = $left.' '.$right;
                if (! isset($seen[$phrase])) {
                    $phrases[$phrase][$index] = $mention->link;
                    $seen[$phrase] = true;
                }
            }
        }

        $rows = [];
        foreach ($this->topics->extract($documents) as $topic) {
            $evidence = [];
            foreach ($mentions as $index => $mention) {
                if (isset($tokensByDocument[$index][$topic->topic])) {
                    $evidence[] = $mention->link;
                }
            }
            $rows[] = ['topic' => $topic->topic, 'count' => $topic->count,
                'share' => MarketingMentionSet::percent($topic->count, count($mentions)),
                'evidenceUrls' => array_slice(array_values(array_unique($evidence)), 0, 5)];
        }
        foreach ($phrases as $phrase => $evidence) {
            if (count($evidence) >= 2) {
                $rows[] = ['topic' => $phrase, 'count' => count($evidence),
                    'share' => MarketingMentionSet::percent(count($evidence), count($mentions)),
                    'evidenceUrls' => array_slice(array_values(array_unique($evidence)), 0, 5)];
            }
        }
        usort($rows, static fn (array $a, array $b): int => ($b['count'] <=> $a['count']) ?: strcmp($a['topic'], $b['topic']));

        return array_slice($rows, 0, $this->config->topicTopLimit());
    }

    public function questions(array $mentions, array $suggestions): array
    {
        $questions = [];
        foreach ($mentions as $index => $mention) {
            preg_match_all('/[^\r\n.!?؟]+[?؟]/u', $mention->title.'. '.$mention->snippet, $matches);
            foreach ($matches[0] ?? [] as $question) {
                $this->addQuestion($questions, $question, 'result', $index, $mention->link);
            }
        }
        foreach ($suggestions as $suggestion) {
            if (preg_match('/[?؟]$/u', $suggestion) === 1 || preg_match('/^(how|what|why|when|where|which|can|does|is|are|как|что|почему|когда|где|какой|какая|какие|сколько|можно)\b/iu', $suggestion) === 1) {
                $this->addQuestion($questions, $suggestion, 'suggestion', null, null);
            }
        }

        $rows = [];
        foreach ($questions as $question) {
            $rows[] = ['question' => $question['question'], 'count' => count($question['documents']),
                'sources' => array_keys($question['sources']),
                'evidenceUrls' => array_slice(array_values(array_unique($question['urls'])), 0, 5)];
        }
        usort($rows, static fn (array $a, array $b): int => ($b['count'] <=> $a['count']) ?: strcmp($a['question'], $b['question']));

        return array_slice($rows, 0, 20);
    }

    public function opportunities(array $topics, array $questions, array $publishers, array $comparison, int $sampleSize): array
    {
        $rows = [];
        foreach (array_slice($topics, 0, 5) as $topic) {
            $rows[] = ['code' => 'topic_coverage', 'params' => ['topic' => $topic['topic'], 'documents' => $topic['count'], 'share' => $topic['share']], 'evidenceUrls' => $topic['evidenceUrls']];
        }
        foreach (array_slice($questions, 0, 5) as $question) {
            $rows[] = ['code' => 'answer_question', 'params' => ['question' => $question['question'], 'documents' => $question['count']], 'evidenceUrls' => $question['evidenceUrls']];
        }
        foreach ($comparison['entities'] as $entity) {
            if ($entity['kind'] === 'brand' && $entity['mentions'] === 0 && $sampleSize > 0) {
                $rows[] = ['code' => 'brand_presence', 'params' => ['brand' => $entity['name'], 'sampleSize' => $sampleSize], 'evidenceUrls' => []];
            }
        }
        foreach (array_slice($publishers, 0, 3) as $publisher) {
            $rows[] = ['code' => 'publisher_outreach', 'params' => ['host' => $publisher['host'], 'documents' => $publisher['count'], 'share' => $publisher['share']], 'evidenceUrls' => $publisher['evidenceUrls']];
        }

        return $rows;
    }

    private function usefulWord(string $word): bool
    {
        return mb_strlen($word) >= $this->config->topicMinWordLength() && ! in_array($word, $this->config->topicStopWords(), true);
    }

    private function addQuestion(array &$questions, string $raw, string $source, ?int $document, ?string $url): void
    {
        $text = trim(preg_replace('/\s+/u', ' ', $raw) ?? $raw);
        if (mb_strlen($text) < 8 || mb_strlen($text) > 240) {
            return;
        }
        $key = mb_strtolower(rtrim($text, "?؟ \t\n\r\0\x0B"));
        $questions[$key] ??= ['question' => $text, 'documents' => [], 'sources' => [], 'urls' => []];
        $questions[$key]['sources'][$source] = true;
        if ($document !== null) {
            $questions[$key]['documents'][$document] = true;
        }
        if ($url !== null) {
            $questions[$key]['urls'][] = $url;
        }
    }
}
