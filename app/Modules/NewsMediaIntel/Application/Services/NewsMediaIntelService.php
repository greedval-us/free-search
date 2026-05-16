<?php

namespace App\Modules\NewsMediaIntel\Application\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class NewsMediaIntelService
{
    public function monitor(string $query): array
    {
        $q = trim($query);
        if ($q === '') {
            return ['query' => '', 'mentions' => [], 'topics' => [], 'timeline' => [], 'sentiment' => ['positive' => 0, 'neutral' => 0, 'negative' => 0]];
        }

        $mentions = [
            ...$this->fetchRss('googlenews', 'https://news.google.com/rss/search?q=' . urlencode($q) . '&hl=ru&gl=RU&ceid=RU:ru'),
            ...$this->fetchRss('bing', 'https://www.bing.com/search?format=rss&q=' . urlencode($q)),
        ];

        $mentions = $this->deduplicate($mentions);

        return [
            'query' => $q,
            'mentions' => array_slice($mentions, 0, 120),
            'topics' => $this->extractTopics($mentions),
            'timeline' => $this->buildTimeline($mentions),
            'sentiment' => $this->sentimentSummary($mentions),
        ];
    }

    private function fetchRss(string $source, string $url): array
    {
        try {
            $response = Http::withHeaders(['Accept' => 'application/rss+xml, application/xml, text/xml;q=0.9, */*;q=0.8'])->timeout(15)->get($url);
        } catch (ConnectionException) {
            return [];
        }
        if (!$response->ok()) {
            return [];
        }

        libxml_use_internal_errors(true);
        $rss = simplexml_load_string((string) $response->body());
        if (!$rss instanceof \SimpleXMLElement) {
            return [];
        }

        $out = [];
        foreach (($rss->channel->item ?? []) as $item) {
            $title = trim((string) ($item->title ?? ''));
            $snippet = trim(strip_tags((string) ($item->description ?? '')));
            $link = trim((string) ($item->link ?? ''));
            $publishedAt = trim((string) ($item->pubDate ?? ''));
            if ($title === '' || $link === '') {
                continue;
            }

            $out[] = compact('source', 'title', 'snippet', 'link', 'publishedAt');
        }

        return $out;
    }

    private function deduplicate(array $mentions): array
    {
        $map = [];
        foreach ($mentions as $mention) {
            $key = mb_strtolower(trim((string) ($mention['link'] ?? '')));
            if ($key === '' || isset($map[$key])) {
                continue;
            }
            $map[$key] = $mention;
        }

        return array_values($map);
    }

    private function extractTopics(array $mentions): array
    {
        $stop = ['the','and','with','from','that','this','for','или','как','что','это','при','после','about'];
        $bucket = [];

        foreach ($mentions as $mention) {
            $text = mb_strtolower(trim(($mention['title'] ?? '') . ' ' . ($mention['snippet'] ?? '')));
            $words = preg_split('/[^\p{L}\p{N}]+/u', $text) ?: [];
            foreach ($words as $word) {
                if (mb_strlen($word) < 4 || in_array($word, $stop, true)) {
                    continue;
                }
                $bucket[$word] = (int) ($bucket[$word] ?? 0) + 1;
            }
        }

        arsort($bucket);
        $topics = [];
        foreach (array_slice($bucket, 0, 20, true) as $word => $count) {
            $topics[] = ['topic' => $word, 'count' => $count];
        }

        return $topics;
    }

    private function buildTimeline(array $mentions): array
    {
        $timeline = [];
        foreach ($mentions as $mention) {
            $raw = (string) ($mention['publishedAt'] ?? '');
            $date = $raw !== '' ? date('Y-m-d', strtotime($raw)) : null;
            if ($date === null || $date === '1970-01-01') {
                continue;
            }
            $timeline[$date] = (int) ($timeline[$date] ?? 0) + 1;
        }

        ksort($timeline);
        $out = [];
        foreach ($timeline as $date => $count) {
            $out[] = ['date' => $date, 'mentions' => $count];
        }

        return $out;
    }

    private function sentimentSummary(array $mentions): array
    {
        $positiveWords = ['success','growth','win','award','profit','улучш','рост','успех'];
        $negativeWords = ['fraud','crime','attack','breach','loss','скандал','утеч','мошенн'];

        $summary = ['positive' => 0, 'neutral' => 0, 'negative' => 0];

        foreach ($mentions as $mention) {
            $text = mb_strtolower(trim(($mention['title'] ?? '') . ' ' . ($mention['snippet'] ?? '')));
            $score = 0;
            foreach ($positiveWords as $word) {
                if (str_contains($text, $word)) {
                    $score++;
                }
            }
            foreach ($negativeWords as $word) {
                if (str_contains($text, $word)) {
                    $score--;
                }
            }

            if ($score > 0) {
                $summary['positive']++;
            } elseif ($score < 0) {
                $summary['negative']++;
            } else {
                $summary['neutral']++;
            }
        }

        return $summary;
    }
}

