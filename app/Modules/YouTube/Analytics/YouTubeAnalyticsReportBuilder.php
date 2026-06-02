<?php

namespace App\Modules\YouTube\Analytics;

use App\Modules\YouTube\Enums\YouTubeDurationBucket;

class YouTubeAnalyticsReportBuilder
{
    /**
     * @param  array<int, array<string, mixed>>  $videos
     * @return array<string, mixed>
     */
    public function totals(array $videos): array
    {
        if ($videos === []) {
            return $this->emptyTotals();
        }

        $views = array_sum(array_column($videos, 'views'));
        $likes = array_sum(array_column($videos, 'likes'));
        $comments = array_sum(array_column($videos, 'comments'));

        return [
            'videos' => count($videos),
            'views' => $views,
            'likes' => $likes,
            'comments' => $comments,
            'avgViews' => (int) round($views / count($videos)),
            'avgLikes' => (int) round($likes / count($videos)),
            'avgComments' => (int) round($comments / count($videos)),
            'medianViews' => $this->median(array_column($videos, 'views')),
            'engagementRate' => $views > 0 ? round((($likes + $comments) / $views) * 100, 2) : 0.0,
            'likeRate' => $views > 0 ? round(($likes / $views) * 100, 2) : 0.0,
            'commentRate' => $views > 0 ? round(($comments / $views) * 100, 2) : 0.0,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $videos
     * @return array<string, mixed>
     */
    public function distribution(array $videos): array
    {
        $timeline = [];
        $duration = YouTubeDurationBucket::emptyDistribution();
        $definition = [];
        $captions = ['with' => 0, 'without' => 0];

        foreach ($videos as $video) {
            $this->appendTimelineRow($timeline, $video);

            $seconds = (int) ($video['durationSeconds'] ?? 0);
            $bucket = YouTubeDurationBucket::fromSeconds($seconds);
            $duration[$bucket->value]++;

            $quality = (string) ($video['definition'] ?? 'unknown');
            $definition[$quality] = ($definition[$quality] ?? 0) + 1;

            $captions[($video['caption'] ?? 'false') === 'true' ? 'with' : 'without']++;
        }

        ksort($timeline);

        return [
            'timeline' => array_values($timeline),
            'duration' => $duration,
            'definition' => $definition,
            'captions' => $captions,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $videos
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function leaders(array $videos): array
    {
        return [
            'byViews' => $this->topBy($videos, 'views'),
            'byLikes' => $this->topBy($videos, 'likes'),
            'byComments' => $this->topBy($videos, 'comments'),
            'byEngagement' => $this->topBy($videos, 'engagementRate'),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $videos
     * @return array<int, array{tag: string, count: int}>
     */
    public function topTags(array $videos): array
    {
        $tags = [];

        foreach ($videos as $video) {
            foreach ((array) ($video['tags'] ?? []) as $tag) {
                $key = mb_strtolower(trim((string) $tag));
                if ($key === '') {
                    continue;
                }

                $tags[$key] = ($tags[$key] ?? 0) + 1;
            }
        }

        arsort($tags);

        return collect($tags)
            ->take(20)
            ->map(fn (int $count, string $tag): array => ['tag' => $tag, 'count' => $count])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $videos
     * @return array<int, array<string, string>>
     */
    public function insights(array $videos, ?array $focusVideo = null): array
    {
        if ($videos === []) {
            return [];
        }

        $totals = $this->totals($videos);
        $top = $this->topBy($videos, 'views')[0] ?? null;
        $duration = $this->distribution($videos)['duration'];
        $dominantDuration = array_keys($duration, max($duration), true)[0] ?? YouTubeDurationBucket::Medium->value;

        return array_values(array_filter([
            $focusVideo !== null ? [
                'key' => 'focus_video',
                'label' => 'Focus video',
                'value' => (string) $focusVideo['title'],
            ] : null,
            $top !== null ? [
                'key' => 'top_video',
                'label' => 'Top video by views',
                'value' => (string) $top['title'],
            ] : null,
            [
                'key' => 'engagement',
                'label' => 'Engagement rate',
                'value' => $totals['engagementRate'].'%',
            ],
            [
                'key' => 'duration_mix',
                'label' => 'Dominant duration',
                'value' => $dominantDuration,
            ],
        ]));
    }

    /**
     * @param  array<int, array<string, mixed>>  $videos
     * @return array<int, array<string, mixed>>
     */
    public function topBy(array $videos, string $field, int $limit = 5): array
    {
        usort($videos, fn (array $a, array $b): int => ($b[$field] ?? 0) <=> ($a[$field] ?? 0));

        return array_slice($videos, 0, $limit);
    }

    /**
     * @return array<string, int|float>
     */
    private function emptyTotals(): array
    {
        return [
            'videos' => 0,
            'views' => 0,
            'likes' => 0,
            'comments' => 0,
            'avgViews' => 0,
            'avgLikes' => 0,
            'avgComments' => 0,
            'medianViews' => 0,
            'engagementRate' => 0.0,
            'likeRate' => 0.0,
            'commentRate' => 0.0,
        ];
    }

    /**
     * @param  array<string, array<string, int|string>>  $timeline
     */
    private function appendTimelineRow(array &$timeline, array $video): void
    {
        $day = substr((string) ($video['publishedAt'] ?? ''), 0, 10);

        if ($day === '') {
            return;
        }

        $timeline[$day] ??= ['key' => $day, 'videos' => 0, 'views' => 0, 'likes' => 0, 'comments' => 0];
        $timeline[$day]['videos']++;
        $timeline[$day]['views'] += (int) ($video['views'] ?? 0);
        $timeline[$day]['likes'] += (int) ($video['likes'] ?? 0);
        $timeline[$day]['comments'] += (int) ($video['comments'] ?? 0);
    }

    /**
     * @param  array<int, int|float>  $values
     */
    private function median(array $values): int
    {
        $values = array_values(array_filter(array_map('intval', $values), fn (int $value): bool => $value >= 0));

        if ($values === []) {
            return 0;
        }

        sort($values);
        $count = count($values);
        $middle = intdiv($count, 2);

        if ($count % 2 === 1) {
            return $values[$middle];
        }

        return (int) round(($values[$middle - 1] + $values[$middle]) / 2);
    }
}
