<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dorks Analytics Report</title>
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
        .container { max-width: 1140px; margin: 0 auto; }
        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            margin-bottom: 16px;
            overflow: hidden;
        }
        .header {
            padding: 20px 22px;
            background: linear-gradient(135deg, #0f172a, #0f766e 55%, #0ea5e9);
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
    </style>
</head>
<body>
@php
    $summary = is_array($report['summary'] ?? null) ? $report['summary'] : [];
    $analytics = is_array($report['analytics'] ?? null) ? $report['analytics'] : [];
    $items = is_array($report['items'] ?? null) ? $report['items'] : [];
@endphp
<main class="container">
    <section class="card">
        <header class="header">
            <h1>Dorks Analytics Report</h1>
            <p>Generated at {{ $generatedAt }}</p>
            <div class="meta">
                <span class="chip">Target: {{ $report['target'] ?? '-' }}</span>
                <span class="chip">Goal: {{ $report['goal'] ?? '-' }}</span>
                <span class="chip">Checked at: {{ $report['checkedAt'] ?? '-' }}</span>
            </div>
        </header>
    </section>

    <section class="card">
        <div class="body">
            <h2>Summary</h2>
            <div class="grid">
                <article class="metric">
                    <div class="label">Total results</div>
                    <div class="value">{{ $summary['total'] ?? 0 }}</div>
                </article>
                <article class="metric">
                    <div class="label">Unique domains</div>
                    <div class="value">{{ $summary['uniqueDomains'] ?? 0 }}</div>
                </article>
                <article class="metric">
                    <div class="label">Sources</div>
                    <div class="value">{{ $summary['sources'] ?? 0 }}</div>
                </article>
                <article class="metric">
                    <div class="label">Goals</div>
                    <div class="value">{{ $summary['goals'] ?? 0 }}</div>
                </article>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>Top Domains</h2>
            <table>
                <thead>
                    <tr>
                        <th>Domain</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($analytics['topDomains'] ?? []) as $row)
                        <tr>
                            <td>{{ $row['label'] ?? '-' }}</td>
                            <td>{{ $row['count'] ?? 0 }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="muted">No data</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>Results</h2>
            <table>
                <thead>
                    <tr>
                        <th>Source</th>
                        <th>Goal</th>
                        <th>Dork</th>
                        <th>Title</th>
                        <th>Domain</th>
                        <th>URL</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $item['source'] ?? '-' }}</td>
                            <td>{{ $item['goal'] ?? '-' }}</td>
                            <td>{{ $item['dork'] ?? '-' }}</td>
                            <td>{{ $item['title'] ?? '-' }}</td>
                            <td>{{ $item['domain'] ?? '-' }}</td>
                            <td>{{ $item['url'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="muted">No results</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>

