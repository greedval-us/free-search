<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Telegram Analytics Report</title>
    <style>
        :root {
            color-scheme: light;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            font-family: "DejaVu Sans", sans-serif;
            color: #0f172a;
            background: #f8fafc;
            line-height: 1.45;
        }

        .container {
            max-width: 1120px;
            margin: 0 auto;
        }

        .card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            margin-bottom: 16px;
            overflow: hidden;
        }

        .header {
            padding: 20px 22px;
            background: linear-gradient(135deg, #0f172a, #1e3a8a 55%, #0891b2);
            color: #ffffff;
        }

        .header h1 {
            margin: 0 0 6px;
            font-size: 22px;
        }

        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px;
        }

        .chip {
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            padding: 4px 10px;
            font-size: 11px;
            white-space: nowrap;
        }

        .body {
            padding: 16px 18px;
        }

        h2 {
            font-size: 14px;
            margin: 0 0 12px;
            color: #0f172a;
        }

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
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
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

        .muted {
            color: #64748b;
        }
    </style>
</head>
<body>
    @php
        $reportLocale = ($locale ?? 'en') === 'ru' ? 'ru' : 'en';

        $dictionary = [
            'en' => [
                'title' => 'Telegram Analytics Report',
                'generatedAt' => 'Generated at',
                'channel' => 'Channel',
                'range' => 'Range',
                'profile' => 'Scoring profile',
                'keyword' => 'Keyword',
                'formula' => 'Formula',
                'totals' => 'Totals',
                'messages' => 'Messages',
                'views' => 'Views',
                'forwards' => 'Forwards',
                'replies' => 'Replies',
                'reactions' => 'Reactions',
                'mediaPosts' => 'Media Posts',
                'avgViewsPerPost' => 'Avg Views/Post',
                'avgInteractionsPerPost' => 'Avg Interactions/Post',
                'timeline' => 'Timeline',
                'bucket' => 'Bucket',
                'interactions' => 'Interactions',
                'topPosts' => 'Top Posts',
                'opinionLeaders' => 'Opinion Leaders',
                'opinionLeadersByDay' => 'Opinion Leaders By Day',
                'date' => 'Date',
                'author' => 'Author',
                'message' => 'Message',
                'score' => 'Score',
                'gifts' => 'Gifts',
                'noTimeline' => 'No timeline data',
                'noTopPosts' => 'No top posts for this range',
                'noOpinionLeaders' => 'Not enough authors to build opinion leaders',
                'noOpinionLeadersByDay' => 'No daily activity data for opinion leaders',
            ],
            'ru' => [
                'title' => 'Отчет по аналитике Telegram',
                'generatedAt' => 'Сформирован',
                'channel' => 'Канал',
                'range' => 'Период',
                'profile' => 'Профиль оценки',
                'keyword' => 'Ключевое слово',
                'formula' => 'Формула',
                'totals' => 'Итоги',
                'messages' => 'Сообщения',
                'views' => 'Просмотры',
                'forwards' => 'Репосты',
                'replies' => 'Ответы',
                'reactions' => 'Реакции',
                'mediaPosts' => 'Посты с медиа',
                'avgViewsPerPost' => 'Сред. просмотров/пост',
                'avgInteractionsPerPost' => 'Сред. взаимодействий/пост',
                'timeline' => 'Динамика',
                'bucket' => 'Срез',
                'interactions' => 'Взаимодействия',
                'topPosts' => 'Топ постов',
                'opinionLeaders' => 'Лидеры мнений',
                'opinionLeadersByDay' => 'Лидеры мнений по дням',
                'date' => 'Дата',
                'author' => 'Автор',
                'message' => 'Сообщение',
                'score' => 'Оценка',
                'gifts' => 'Подарки',
                'noTimeline' => 'Нет данных по динамике',
                'noTopPosts' => 'Нет топ-постов за выбранный период',
                'noOpinionLeaders' => 'Недостаточно авторов для оценки лидеров мнений',
                'noOpinionLeadersByDay' => 'Нет дневных данных активности по лидерам мнений',
            ],
        ];

        $tr = $dictionary[$reportLocale];
    @endphp
    <main class="container">
        <section class="card">
            <header class="header">
                <h1>{{ $tr['title'] }}</h1>
                <p>{{ $tr['generatedAt'] }} {{ $generatedAt }}</p>
                <div class="meta">
                    <span class="chip">{{ $tr['channel'] }}: {{ $report['range']['chatUsername'] ?? '-' }}</span>
                    <span class="chip">{{ $tr['range'] }}: {{ $report['range']['label'] ?? '-' }}</span>
                    <span class="chip">{{ $tr['profile'] }}: {{ $report['score']['priority'] ?? 'balanced' }}</span>
                    @if(!empty($report['range']['keyword']))
                        <span class="chip">{{ $tr['keyword'] }}: {{ $report['range']['keyword'] }}</span>
                    @endif
                    <span class="chip">
                        {{ $tr['formula'] }}:
                        {{ $report['score']['weights']['views'] ?? 0 }}*V +
                        {{ $report['score']['weights']['forwards'] ?? 0 }}*F +
                        {{ $report['score']['weights']['replies'] ?? 0 }}*R +
                        {{ $report['score']['weights']['reactions'] ?? 0 }}*Re +
                        {{ $report['score']['weights']['gifts'] ?? 0 }}*G
                    </span>
                </div>
            </header>
        </section>

        <section class="card">
            <div class="body">
                <h2>{{ $tr['totals'] }}</h2>
                <div class="grid">
                    <article class="metric">
                        <div class="label">{{ $tr['messages'] }}</div>
                        <div class="value">{{ $report['summary']['totals']['messages'] ?? 0 }}</div>
                    </article>
                    <article class="metric">
                        <div class="label">{{ $tr['views'] }}</div>
                        <div class="value">{{ $report['summary']['totals']['views'] ?? 0 }}</div>
                    </article>
                    <article class="metric">
                        <div class="label">{{ $tr['forwards'] }}</div>
                        <div class="value">{{ $report['summary']['totals']['forwards'] ?? 0 }}</div>
                    </article>
                    <article class="metric">
                        <div class="label">{{ $tr['replies'] }}</div>
                        <div class="value">{{ $report['summary']['totals']['replies'] ?? 0 }}</div>
                    </article>
                    <article class="metric">
                        <div class="label">{{ $tr['reactions'] }}</div>
                        <div class="value">{{ $report['summary']['totals']['reactions'] ?? 0 }}</div>
                    </article>
                    <article class="metric">
                        <div class="label">{{ $tr['mediaPosts'] }}</div>
                        <div class="value">{{ $report['summary']['totals']['mediaPosts'] ?? 0 }}</div>
                    </article>
                    <article class="metric">
                        <div class="label">{{ $tr['avgViewsPerPost'] }}</div>
                        <div class="value">{{ $report['summary']['totals']['avgViewsPerPost'] ?? 0 }}</div>
                    </article>
                    <article class="metric">
                        <div class="label">{{ $tr['avgInteractionsPerPost'] }}</div>
                        <div class="value">{{ $report['summary']['totals']['avgInteractionsPerPost'] ?? 0 }}</div>
                    </article>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="body">
                <h2>{{ $tr['timeline'] }}</h2>
                <table>
                    <thead>
                        <tr>
                            <th>{{ $tr['bucket'] }}</th>
                            <th>{{ $tr['messages'] }}</th>
                            <th>{{ $tr['views'] }}</th>
                            <th>{{ $tr['forwards'] }}</th>
                            <th>{{ $tr['replies'] }}</th>
                            <th>{{ $tr['reactions'] }}</th>
                            <th>{{ $tr['interactions'] }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($report['summary']['timeline'] ?? []) as $item)
                            <tr>
                                <td>{{ $item['label'] ?? '-' }}</td>
                                <td>{{ $item['messages'] ?? 0 }}</td>
                                <td>{{ $item['views'] ?? 0 }}</td>
                                <td>{{ $item['forwards'] ?? 0 }}</td>
                                <td>{{ $item['replies'] ?? 0 }}</td>
                                <td>{{ $item['reactions'] ?? 0 }}</td>
                                <td>{{ $item['interactions'] ?? 0 }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="muted">{{ $tr['noTimeline'] }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <div class="body">
                <h2>{{ $tr['topPosts'] }}</h2>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ $tr['date'] }}</th>
                            <th>{{ $tr['message'] }}</th>
                            <th>{{ $tr['views'] }}</th>
                            <th>{{ $tr['forwards'] }}</th>
                            <th>{{ $tr['replies'] }}</th>
                            <th>{{ $tr['reactions'] }}</th>
                            <th>{{ $tr['score'] }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($report['summary']['topPosts'] ?? []) as $index => $post)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    @if(isset($post['date']))
                                        {{ \Carbon\Carbon::createFromTimestamp((int) $post['date'], config('app.timezone'))->format('d.m.Y H:i') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $post['message'] ?? '-' }}</td>
                                <td>{{ $post['views'] ?? 0 }}</td>
                                <td>{{ $post['forwards'] ?? 0 }}</td>
                                <td>{{ $post['replies'] ?? 0 }}</td>
                                <td>{{ $post['reactions'] ?? 0 }}</td>
                                <td>{{ $post['score'] ?? 0 }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="muted">{{ $tr['noTopPosts'] }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <div class="body">
                <h2>{{ $tr['opinionLeaders'] }}</h2>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ $tr['author'] }}</th>
                            <th>{{ $tr['messages'] }}</th>
                            <th>{{ $tr['forwards'] }}</th>
                            <th>{{ $tr['replies'] }}</th>
                            <th>{{ $tr['reactions'] }}</th>
                            <th>{{ $tr['gifts'] }}</th>
                            <th>{{ $tr['interactions'] }}</th>
                            <th>{{ $tr['score'] }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(count($report['summary']['opinionLeaders'] ?? []) > 1)
                            @foreach(($report['summary']['opinionLeaders'] ?? []) as $index => $leader)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $leader['authorLabel'] ?? ('ID ' . ($leader['authorId'] ?? '-')) }}</td>
                                    <td>{{ $leader['messages'] ?? 0 }}</td>
                                    <td>{{ $leader['forwards'] ?? 0 }}</td>
                                    <td>{{ $leader['replies'] ?? 0 }}</td>
                                    <td>{{ $leader['reactions'] ?? 0 }}</td>
                                    <td>{{ $leader['gifts'] ?? 0 }}</td>
                                    <td>{{ $leader['interactions'] ?? 0 }}</td>
                                    <td>{{ $leader['score'] ?? 0 }}</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="9" class="muted">{{ $tr['noOpinionLeaders'] }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <div class="body">
                <h2>{{ $tr['opinionLeadersByDay'] }}</h2>
                <table>
                    <thead>
                        <tr>
                            <th>{{ $tr['date'] }}</th>
                            <th>{{ $tr['author'] }}</th>
                            <th>{{ $tr['messages'] }}</th>
                            <th>{{ $tr['forwards'] }}</th>
                            <th>{{ $tr['replies'] }}</th>
                            <th>{{ $tr['reactions'] }}</th>
                            <th>{{ $tr['gifts'] }}</th>
                            <th>{{ $tr['interactions'] }}</th>
                            <th>{{ $tr['score'] }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($report['summary']['opinionLeadersDaily'] ?? []) as $row)
                            <tr>
                                <td>{{ $row['dayLabel'] ?? '-' }}</td>
                                <td>{{ $row['authorLabel'] ?? ('ID ' . ($row['authorId'] ?? '-')) }}</td>
                                <td>{{ $row['messages'] ?? 0 }}</td>
                                <td>{{ $row['forwards'] ?? 0 }}</td>
                                <td>{{ $row['replies'] ?? 0 }}</td>
                                <td>{{ $row['reactions'] ?? 0 }}</td>
                                <td>{{ $row['gifts'] ?? 0 }}</td>
                                <td>{{ $row['interactions'] ?? 0 }}</td>
                                <td>{{ $row['score'] ?? 0 }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="muted">{{ $tr['noOpinionLeadersByDay'] }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
