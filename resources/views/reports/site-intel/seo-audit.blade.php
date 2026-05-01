<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SEO Audit Report</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; font-family: "DejaVu Sans", sans-serif; color: #0f172a; background: #f8fafc; }
        .container { max-width: 1120px; margin: 0 auto; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; margin-bottom: 16px; overflow: hidden; }
        .header { padding: 20px 22px; background: linear-gradient(135deg, #0f172a, #0369a1 55%, #0891b2); color: #fff; }
        .header h1 { margin: 0 0 8px; font-size: 22px; }
        .body { padding: 16px 18px; }
        .grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
        .metric { border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; background: #f8fafc; }
        .label { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: .05em; }
        .value { margin-top: 6px; font-size: 20px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; border-bottom: 1px solid #e2e8f0; padding: 8px 6px; font-size: 12px; vertical-align: top; }
        th { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: .05em; }
        h2 { margin: 0 0 10px; font-size: 14px; }
        ul { margin: 0; padding-left: 16px; }
    </style>
</head>
<body>
@php
    $isRu = ($locale ?? 'en') === 'ru';
    $t = $isRu
        ? [
            'title' => 'SEO-аудит: отчет',
            'generatedAt' => 'Сформирован',
            'target' => 'Цель',
            'checkedAt' => 'Проверено',
            'score' => 'SEO балл',
            'httpStatus' => 'HTTP статус',
            'ttfb' => 'TTFB',
            'crawledPages' => 'Просканировано страниц',
            'crawlSummary' => 'Сводка краулинга',
            'duplicates' => 'Дубликаты',
            'canonical' => 'Canonical аудит',
            'hreflang' => 'Hreflang аудит',
            'sitemap' => 'Sitemap аудит',
            'recommendations' => 'Рекомендации',
            'none' => 'Нет данных',
        ]
        : [
            'title' => 'SEO Audit Report',
            'generatedAt' => 'Generated at',
            'target' => 'Target',
            'checkedAt' => 'Checked at',
            'score' => 'SEO score',
            'httpStatus' => 'HTTP status',
            'ttfb' => 'TTFB',
            'crawledPages' => 'Crawled pages',
            'crawlSummary' => 'Crawl summary',
            'duplicates' => 'Duplicates',
            'canonical' => 'Canonical audit',
            'hreflang' => 'Hreflang audit',
            'sitemap' => 'Sitemap audit',
            'recommendations' => 'Recommendations',
            'none' => 'No data',
        ];

    $crawl = $report['crawl'] ?? [];
    $duplicates = $crawl['duplicates'] ?? [];
    $canonical = $crawl['canonicalAudit'] ?? [];
    $hreflang = $crawl['hreflangAudit'] ?? [];
    $sitemapAudit = $report['sitemapAudit'] ?? [];
@endphp

<main class="container">
    <section class="card">
        <header class="header">
            <h1>{{ $t['title'] }}</h1>
            <p>{{ $t['generatedAt'] }} {{ $generatedAt }}</p>
            <p>{{ $t['target'] }}: {{ $report['target']['finalUrl'] ?? '-' }}</p>
            <p>{{ $t['checkedAt'] }}: {{ $report['checkedAt'] ?? '-' }}</p>
        </header>
    </section>

    <section class="card">
        <div class="body">
            <div class="grid">
                <article class="metric"><div class="label">{{ $t['score'] }}</div><div class="value">{{ $report['score']['value'] ?? 0 }}</div></article>
                <article class="metric"><div class="label">{{ $t['httpStatus'] }}</div><div class="value">{{ $report['status']['httpCode'] ?? 0 }}</div></article>
                <article class="metric"><div class="label">{{ $t['ttfb'] }}</div><div class="value">{{ $report['performance']['ttfbMsApprox'] ?? 0 }} ms</div></article>
                <article class="metric"><div class="label">{{ $t['crawledPages'] }}</div><div class="value">{{ $crawl['scanned'] ?? 0 }}/{{ $crawl['limit'] ?? 0 }}</div></article>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $t['crawlSummary'] }}</h2>
            @if(($crawl['pages'] ?? []) === [])
                <p>{{ $t['none'] }}</p>
            @else
                <table>
                    <thead><tr><th>URL</th><th>Status</th><th>Title</th><th>H1</th><th>Indexable</th></tr></thead>
                    <tbody>
                    @foreach(($crawl['pages'] ?? []) as $page)
                        <tr>
                            <td>{{ $page['url'] ?? '-' }}</td>
                            <td>{{ $page['status'] ?? '-' }}</td>
                            <td>{{ $page['title'] ?? '-' }}</td>
                            <td>{{ $page['h1Count'] ?? '-' }}</td>
                            <td>{{ ($page['indexable'] ?? false) ? 'yes' : 'no' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $t['duplicates'] }}</h2>
            <p>Titles: {{ count($duplicates['titles'] ?? []) }}, Descriptions: {{ count($duplicates['descriptions'] ?? []) }}, H1: {{ count($duplicates['h1'] ?? []) }}</p>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $t['canonical'] }}</h2>
            <p>Missing: {{ count($canonical['missing'] ?? []) }}, Cross-domain: {{ count($canonical['crossDomain'] ?? []) }}, Invalid: {{ count($canonical['invalid'] ?? []) }}</p>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $t['hreflang'] }}</h2>
            <p>Pages with hreflang: {{ $hreflang['pagesWithHreflang'] ?? 0 }}</p>
            <p>Pages without self-reference: {{ count($hreflang['pagesWithoutSelfReference'] ?? []) }}</p>
            <p>Duplicate language tags: {{ count($hreflang['duplicateLangTags'] ?? []) }}</p>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $t['sitemap'] }}</h2>
            <p>Sampled URLs: {{ $sitemapAudit['sampled'] ?? 0 }}</p>
            <p>Non-200 URLs: {{ count($sitemapAudit['non200'] ?? []) }}</p>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $t['recommendations'] }}</h2>
            @if(($report['recommendations'] ?? []) === [])
                <p>{{ $t['none'] }}</p>
            @else
                <ul>
                    @foreach(($report['recommendations'] ?? []) as $item)
                        <li>[{{ strtoupper((string) ($item['priority'] ?? 'low')) }}] {{ (string) ($item['key'] ?? '-') }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>
</main>
</body>
</html>

