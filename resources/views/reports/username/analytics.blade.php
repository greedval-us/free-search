<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Username Analytics Report</title>
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
            font-size: 22px;
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
            'title' => 'Username Analytics Report',
            'generatedAt' => 'Generated at',
            'username' => 'Username',
            'checkedAt' => 'Checked at',
            'totals' => 'Totals',
            'checked' => 'Checked',
            'found' => 'Found',
            'notFound' => 'Not Found',
            'unknown' => 'Unknown',
            'confidence' => 'Confidence',
            'avgConfidence' => 'Avg confidence',
            'highConfidence' => 'High confidence',
            'mediumConfidence' => 'Medium confidence',
            'lowConfidence' => 'Low confidence',
            'similarity' => 'Similarity variants',
            'variant' => 'Variant',
            'reason' => 'Reason',
            'prioritySources' => 'Priority sources',
            'noSimilarity' => 'No similarity variants',
            'platforms' => 'Platforms',
            'platform' => 'Platform',
            'status' => 'Status',
            'category' => 'Category',
            'region' => 'Region',
            'domain' => 'Domain',
            'profile' => 'Profile',
            'noItems' => 'No platform results',
            'status_found' => 'Found',
            'status_not_found' => 'Not found',
            'status_unknown' => 'Unknown',
        ],
        'ru' => [
            'title' => 'Отчёт по аналитике Username',
            'generatedAt' => 'Сформирован',
            'username' => 'Username',
            'checkedAt' => 'Проверено',
            'totals' => 'Итоги',
            'checked' => 'Проверено',
            'found' => 'Найдено',
            'notFound' => 'Не найдено',
            'unknown' => 'Неизвестно',
            'confidence' => 'Уверенность',
            'avgConfidence' => 'Средняя уверенность',
            'highConfidence' => 'Высокая уверенность',
            'mediumConfidence' => 'Средняя уверенность',
            'lowConfidence' => 'Низкая уверенность',
            'similarity' => 'Похожие варианты',
            'variant' => 'Вариант',
            'reason' => 'Причина',
            'prioritySources' => 'Приоритетные источники',
            'noSimilarity' => 'Похожие варианты не найдены',
            'platforms' => 'Площадки',
            'platform' => 'Площадка',
            'status' => 'Статус',
            'category' => 'Категория',
            'region' => 'Регион',
            'domain' => 'Домен',
            'profile' => 'Профиль',
            'noItems' => 'Нет результатов по площадкам',
            'status_found' => 'Найдено',
            'status_not_found' => 'Не найдено',
            'status_unknown' => 'Неизвестно',
        ],
    ][$reportLocale];

    $summary = $report['summary'] ?? [];
    $analytics = $report['analytics'] ?? [];
    $confidence = is_array($analytics['confidence'] ?? null) ? $analytics['confidence'] : [];
    $variants = is_array($analytics['similarity']['variants'] ?? null) ? $analytics['similarity']['variants'] : [];
    $items = is_array($report['items'] ?? null) ? $report['items'] : [];
@endphp
<main class="container">
    <section class="card">
        <header class="header">
            <h1>{{ $tr['title'] }}</h1>
            <p>{{ $tr['generatedAt'] }} {{ $generatedAt }}</p>
            <div class="meta">
                <span class="chip">{{ $tr['username'] }}: {{ $report['username'] ?? '-' }}</span>
                <span class="chip">{{ $tr['checkedAt'] }}: {{ $report['checkedAt'] ?? '-' }}</span>
            </div>
        </header>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['totals'] }}</h2>
            <div class="grid">
                <article class="metric">
                    <div class="label">{{ $tr['checked'] }}</div>
                    <div class="value">{{ $summary['checked'] ?? 0 }}</div>
                </article>
                <article class="metric">
                    <div class="label">{{ $tr['found'] }}</div>
                    <div class="value">{{ $summary['found'] ?? 0 }}</div>
                </article>
                <article class="metric">
                    <div class="label">{{ $tr['notFound'] }}</div>
                    <div class="value">{{ $summary['notFound'] ?? 0 }}</div>
                </article>
                <article class="metric">
                    <div class="label">{{ $tr['unknown'] }}</div>
                    <div class="value">{{ $summary['unknown'] ?? 0 }}</div>
                </article>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['confidence'] }}</h2>
            <div class="grid">
                <article class="metric">
                    <div class="label">{{ $tr['avgConfidence'] }}</div>
                    <div class="value">{{ $confidence['average'] ?? 0 }}%</div>
                </article>
                <article class="metric">
                    <div class="label">{{ $tr['highConfidence'] }}</div>
                    <div class="value">{{ $confidence['high'] ?? 0 }}</div>
                </article>
                <article class="metric">
                    <div class="label">{{ $tr['mediumConfidence'] }}</div>
                    <div class="value">{{ $confidence['medium'] ?? 0 }}</div>
                </article>
                <article class="metric">
                    <div class="label">{{ $tr['lowConfidence'] }}</div>
                    <div class="value">{{ $confidence['low'] ?? 0 }}</div>
                </article>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['similarity'] }}</h2>
            <table>
                <thead>
                    <tr>
                        <th>{{ $tr['variant'] }}</th>
                        <th>{{ $tr['reason'] }}</th>
                        <th>{{ $tr['prioritySources'] }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($variants as $variant)
                        <tr>
                            <td>{{ $variant['username'] ?? '-' }}</td>
                            <td>{{ $variant['reason'] ?? '-' }}</td>
                            <td>
                                @if(isset($variant['foundInPrioritySources'], $variant['checkedPrioritySources']))
                                    {{ $variant['foundInPrioritySources'] }}/{{ $variant['checkedPrioritySources'] }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="muted">{{ $tr['noSimilarity'] }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['platforms'] }}</h2>
            <table>
                <thead>
                    <tr>
                        <th>{{ $tr['platform'] }}</th>
                        <th>{{ $tr['status'] }}</th>
                        <th>{{ $tr['category'] }}</th>
                        <th>{{ $tr['region'] }}</th>
                        <th>{{ $tr['domain'] }}</th>
                        <th>HTTP</th>
                        <th>{{ $tr['confidence'] }}</th>
                        <th>{{ $tr['profile'] }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php
                            $status = (string) ($item['status'] ?? 'unknown');
                            $statusLabel = match ($status) {
                                'found' => $tr['status_found'],
                                'not_found' => $tr['status_not_found'],
                                default => $tr['status_unknown'],
                            };
                        @endphp
                        <tr>
                            <td>{{ $item['name'] ?? '-' }}</td>
                            <td>{{ $statusLabel }}</td>
                            <td>{{ $item['category'] ?? '-' }}</td>
                            <td>{{ $item['regionGroup'] ?? '-' }}</td>
                            <td>{{ $item['profileDomain'] ?? '-' }}</td>
                            <td>{{ $item['httpStatus'] ?? '-' }}</td>
                            <td>{{ $item['confidence'] ?? 0 }}%</td>
                            <td>{{ $item['profileUrl'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="muted">{{ $tr['noItems'] }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
