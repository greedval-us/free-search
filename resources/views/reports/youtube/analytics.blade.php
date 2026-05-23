<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>YouTube Analytics Report</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 24px;
            font-family: "DejaVu Sans", sans-serif;
            color: #0f172a;
            background: #f8fafc;
            line-height: 1.45;
        }
        .container { max-width: 1120px; margin: 0 auto; }
        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            margin-bottom: 16px;
            overflow: hidden;
        }
        .header {
            padding: 20px 22px;
            background: linear-gradient(135deg, #0f172a, #1e3a8a 55%, #0891b2);
            color: #fff;
        }
        .header h1 { margin: 0 0 6px; font-size: 22px; }
        .meta { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
        .chip {
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            padding: 4px 10px;
            font-size: 11px;
            white-space: nowrap;
        }
        .body { padding: 16px 18px; }
        h2 { font-size: 14px; margin: 0 0 12px; color: #0f172a; }
        .grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }
        .metric {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 12px;
            background: #f8fafc;
        }
        .metric .label {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .metric .value {
            margin-top: 6px;
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            padding: 8px 6px;
            font-size: 12px;
            vertical-align: top;
        }
        th {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .muted { color: #64748b; }
        .tag-row { display: flex; flex-wrap: wrap; gap: 8px; }
        .tag-chip {
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 11px;
            background: #f8fafc;
        }
        @media (max-width: 960px) {
            .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 640px) {
            .grid { grid-template-columns: repeat(1, minmax(0, 1fr)); }
        }
    </style>
</head>
<body>
@php
    $reportLocale = ($locale ?? 'en') === 'ru' ? 'ru' : 'en';
    $tr = [
        'en' => [
            'title' => 'YouTube Analytics Report',
            'generatedAt' => 'Generated at',
            'mode' => 'Mode',
            'target' => 'Target',
            'totals' => 'Totals',
            'videos' => 'Videos',
            'views' => 'Views',
            'likes' => 'Likes',
            'comments' => 'Comments',
            'avgViews' => 'Avg views',
            'engagementRate' => 'Engagement rate',
            'insights' => 'Quick insights',
            'metric' => 'Metric',
            'value' => 'Value',
            'timeline' => 'Publishing timeline',
            'date' => 'Date',
            'duration' => 'Duration distribution',
            'leadersByViews' => 'Leaders by views',
            'leadersByLikes' => 'Leaders by likes',
            'leadersByEngagement' => 'Leaders by engagement',
            'leadersByComments' => 'Leaders by comments',
            'video' => 'Video',
            'tags' => 'Top tags',
            'none' => 'No data',
        ],
        'ru' => [
            'title' => 'Отчёт YouTube Analytics',
            'generatedAt' => 'Сформирован',
            'mode' => 'Режим',
            'target' => 'Цель',
            'totals' => 'Итоги',
            'videos' => 'Видео',
            'views' => 'Просмотры',
            'likes' => 'Лайки',
            'comments' => 'Комментарии',
            'avgViews' => 'Средние просмотры',
            'engagementRate' => 'Вовлечённость',
            'insights' => 'Быстрые выводы',
            'metric' => 'Показатель',
            'value' => 'Значение',
            'timeline' => 'Динамика публикаций',
            'date' => 'Дата',
            'duration' => 'Распределение длительности',
            'leadersByViews' => 'Лидеры по просмотрам',
            'leadersByLikes' => 'Лидеры по лайкам',
            'leadersByEngagement' => 'Лидеры по вовлечённости',
            'leadersByComments' => 'Лидеры по комментариям',
            'video' => 'Видео',
            'tags' => 'Топ теги',
            'none' => 'Нет данных',
        ],
    ][$reportLocale];

    $mode = (string) ($report['mode'] ?? '-');
    $target = $mode === 'video'
        ? (string) ($report['video']['id'] ?? '-')
        : (string) ($report['channel']['id'] ?? '-');

    $duration = $report['distribution']['duration'] ?? [];
@endphp

<main class="container">
    <section class="card">
        <header class="header">
            <h1>{{ $tr['title'] }}</h1>
            <p>{{ $tr['generatedAt'] }} {{ $generatedAt }}</p>
            <div class="meta">
                <span class="chip">{{ $tr['mode'] }}: {{ $mode }}</span>
                <span class="chip">{{ $tr['target'] }}: {{ $target !== '' ? $target : '-' }}</span>
            </div>
        </header>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['totals'] }}</h2>
            <div class="grid">
                <article class="metric">
                    <div class="label">{{ $tr['videos'] }}</div>
                    <div class="value">{{ $report['totals']['videos'] ?? 0 }}</div>
                </article>
                <article class="metric">
                    <div class="label">{{ $tr['views'] }}</div>
                    <div class="value">{{ $report['totals']['views'] ?? 0 }}</div>
                </article>
                <article class="metric">
                    <div class="label">{{ $tr['likes'] }}</div>
                    <div class="value">{{ $report['totals']['likes'] ?? 0 }}</div>
                </article>
                <article class="metric">
                    <div class="label">{{ $tr['comments'] }}</div>
                    <div class="value">{{ $report['totals']['comments'] ?? 0 }}</div>
                </article>
                <article class="metric">
                    <div class="label">{{ $tr['avgViews'] }}</div>
                    <div class="value">{{ $report['totals']['avgViews'] ?? 0 }}</div>
                </article>
                <article class="metric">
                    <div class="label">{{ $tr['engagementRate'] }}</div>
                    <div class="value">{{ $report['totals']['engagementRate'] ?? 0 }}%</div>
                </article>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['insights'] }}</h2>
            <table>
                <thead>
                <tr>
                    <th>{{ $tr['metric'] }}</th>
                    <th>{{ $tr['value'] }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse(($report['insights'] ?? []) as $insight)
                    <tr>
                        <td>{{ $insight['label'] ?? '-' }}</td>
                        <td>{{ $insight['value'] ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="muted">{{ $tr['none'] }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['timeline'] }}</h2>
            <table>
                <thead>
                <tr>
                    <th>{{ $tr['date'] }}</th>
                    <th>{{ $tr['videos'] }}</th>
                    <th>{{ $tr['views'] }}</th>
                    <th>{{ $tr['likes'] }}</th>
                    <th>{{ $tr['comments'] }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse(($report['distribution']['timeline'] ?? []) as $row)
                    <tr>
                        <td>{{ $row['key'] ?? '-' }}</td>
                        <td>{{ $row['videos'] ?? 0 }}</td>
                        <td>{{ $row['views'] ?? 0 }}</td>
                        <td>{{ $row['likes'] ?? 0 }}</td>
                        <td>{{ $row['comments'] ?? 0 }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="muted">{{ $tr['none'] }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['duration'] }}</h2>
            <table>
                <tbody>
                <tr><th>short</th><td>{{ $duration['short'] ?? 0 }}</td></tr>
                <tr><th>medium</th><td>{{ $duration['medium'] ?? 0 }}</td></tr>
                <tr><th>long</th><td>{{ $duration['long'] ?? 0 }}</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['leadersByViews'] }}</h2>
            <table>
                <thead>
                <tr>
                    <th>{{ $tr['video'] }}</th>
                    <th>{{ $tr['views'] }}</th>
                    <th>{{ $tr['likes'] }}</th>
                    <th>{{ $tr['comments'] }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse(($report['leaders']['byViews'] ?? []) as $video)
                    <tr>
                        <td>{{ $video['title'] ?? '-' }}</td>
                        <td>{{ $video['views'] ?? 0 }}</td>
                        <td>{{ $video['likes'] ?? 0 }}</td>
                        <td>{{ $video['comments'] ?? 0 }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="muted">{{ $tr['none'] }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['leadersByLikes'] }}</h2>
            <table>
                <thead>
                <tr>
                    <th>{{ $tr['video'] }}</th>
                    <th>{{ $tr['likes'] }}</th>
                    <th>{{ $tr['views'] }}</th>
                    <th>{{ $tr['comments'] }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse(($report['leaders']['byLikes'] ?? []) as $video)
                    <tr>
                        <td>{{ $video['title'] ?? '-' }}</td>
                        <td>{{ $video['likes'] ?? 0 }}</td>
                        <td>{{ $video['views'] ?? 0 }}</td>
                        <td>{{ $video['comments'] ?? 0 }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="muted">{{ $tr['none'] }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['leadersByEngagement'] }}</h2>
            <table>
                <thead>
                <tr>
                    <th>{{ $tr['video'] }}</th>
                    <th>{{ $tr['engagementRate'] }}</th>
                    <th>{{ $tr['views'] }}</th>
                    <th>{{ $tr['likes'] }}</th>
                    <th>{{ $tr['comments'] }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse(($report['leaders']['byEngagement'] ?? []) as $video)
                    <tr>
                        <td>{{ $video['title'] ?? '-' }}</td>
                        <td>{{ number_format((float) ($video['engagementRate'] ?? 0), 2, '.', ' ') }}%</td>
                        <td>{{ $video['views'] ?? 0 }}</td>
                        <td>{{ $video['likes'] ?? 0 }}</td>
                        <td>{{ $video['comments'] ?? 0 }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="muted">{{ $tr['none'] }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['leadersByComments'] }}</h2>
            <table>
                <thead>
                <tr>
                    <th>{{ $tr['video'] }}</th>
                    <th>{{ $tr['comments'] }}</th>
                    <th>{{ $tr['views'] }}</th>
                    <th>{{ $tr['likes'] }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse(($report['leaders']['byComments'] ?? []) as $video)
                    <tr>
                        <td>{{ $video['title'] ?? '-' }}</td>
                        <td>{{ $video['comments'] ?? 0 }}</td>
                        <td>{{ $video['views'] ?? 0 }}</td>
                        <td>{{ $video['likes'] ?? 0 }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="muted">{{ $tr['none'] }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['tags'] }}</h2>
            <div class="tag-row">
                @forelse(($report['topTags'] ?? []) as $tag)
                    <span class="tag-chip">{{ $tag['tag'] ?? '-' }} ({{ $tag['count'] ?? 0 }})</span>
                @empty
                    <span class="muted">{{ $tr['none'] }}</span>
                @endforelse
            </div>
        </div>
    </section>
</main>
</body>
</html>
