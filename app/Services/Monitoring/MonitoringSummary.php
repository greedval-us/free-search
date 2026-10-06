<?php

namespace App\Services\Monitoring;

use App\Modules\NewsMediaIntel\Application\Services\NewsMediaIntel\NewsMentionFingerprintFactory;
use App\Modules\NewsMediaIntel\Application\Support\NewsMediaIntelConfig;
use Carbon\CarbonImmutable;

/** Bounded lexical grouping of saved metadata. No semantic/AI inference. */
final class MonitoringSummary
{
    public function __construct(private readonly NewsMentionFingerprintFactory $fingerprints, private readonly NewsMediaIntelConfig $newsConfig) {}

    public function accumulate(array &$state, array $item, string $timezone): void
    {
        $link = $this->fingerprints->linkKey($item['url']);
        $text = $item['title'] ?: mb_substr($item['text'], 0, 240);
        $content = $this->fingerprints->contentKey($text, '');
        $contentKey = mb_strlen($content) >= 20 ? hash('sha256', $content) : null;
        $groupKey = $state['links'][$link] ?? ($contentKey === null ? null : ($state['contents'][$contentKey] ?? null)) ?? hash('sha256', $link);
        $state['links'][$link] = $groupKey;
        if ($contentKey !== null) {
            $state['contents'][$contentKey] = $groupKey;
        }
        $group = $state['groups'][$groupKey] ?? ['text' => mb_substr($text, 0, 240), 'urls' => [], 'material_ids' => [],
            'count' => 0, 'platforms' => [], 'first_published_at' => $item['published_at'], 'last_published_at' => $item['published_at']];
        $group['count']++;
        $group['platforms'][$item['platform']] = true;
        if (count($group['material_ids']) < 20) {
            $group['material_ids'][] = $item['id'];
            $group['urls'][] = $item['url'];
        }
        if ($item['published_at'] !== null) {
            $group['first_published_at'] = min($group['first_published_at'] ?? $item['published_at'], $item['published_at']);
            $group['last_published_at'] = max($group['last_published_at'] ?? $item['published_at'], $item['published_at']);
            $day = CarbonImmutable::parse($item['published_at'])->setTimezone($timezone)->toDateString();
            $state['timeline'][$day] = ($state['timeline'][$day] ?? 0) + 1;
        }
        $state['groups'][$groupKey] = $group;
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text.' '.mb_substr($item['text'], 0, 512))) ?: [];
        $stop = array_fill_keys($this->newsConfig->topicStopWords(), true);
        foreach (array_unique(array_slice($words, 0, (int) config('monitoring.max_topic_words'))) as $word) {
            if (mb_strlen($word) < $this->newsConfig->topicMinWordLength() || isset($stop[$word])) {
                continue;
            }
            if (! isset($state['topics'][$word]) && count($state['topics'] ?? []) >= config('monitoring.max_topic_terms')) {
                continue;
            }
            $topic = $state['topics'][$word] ?? ['term' => $word, 'count' => 0, 'material_ids' => [], 'urls' => []];
            $topic['count']++;
            if (count($topic['material_ids']) < 20) {
                $topic['material_ids'][] = $item['id'];
                $topic['urls'][] = $item['url'];
            }
            $state['topics'][$word] = $topic;
        }
    }

    public function finish(array $state): array
    {
        $groups = array_values($state['groups'] ?? []);
        usort($groups, static fn ($a, $b) => $b['count'] <=> $a['count'] ?: strcmp($b['last_published_at'] ?? '', $a['last_published_at'] ?? ''));
        $topics = array_values(array_filter($state['topics'] ?? [], static fn ($t) => $t['count'] >= 2));
        usort($topics, static fn ($a, $b) => $b['count'] <=> $a['count'] ?: strcmp($a['term'], $b['term']));
        $timeline = $state['timeline'] ?? [];
        ksort($timeline);

        return ['bullets' => array_map(static function ($g) {
            $g['platforms'] = array_keys($g['platforms']);
            $g['urls'] = array_values(array_unique($g['urls']));

            return $g;
        }, array_slice($groups, 0, 7)),
            'recurring_topics' => array_slice($topics, 0, 10), 'timeline' => $timeline, 'group_count' => count($groups),
            'grouping' => 'normalized-url-preserving-identity-query-or-exact-normalized-title/excerpt', 'topic_method' => 'repeated-words-per-publication-with-stopwords'];
    }
}
