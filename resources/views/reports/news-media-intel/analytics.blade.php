<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('news_media_intel.report.title') }}</title>
    <style>
        :root { color-scheme: light; } * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; color: #0f172a; background: #f8fafc; font: 14px/1.6 "DejaVu Sans", sans-serif; }
        main { max-width: 1120px; margin: auto; } section, header { padding: 22px; margin-bottom: 16px; border: 1px solid #e2e8f0; border-radius: 12px; background: white; }
        header { background: #082f49; color: white; } h1 { font-size: 25px; } h2 { font-size: 18px; } h3 { font-size: 15px; }
        table { width: 100%; border-collapse: collapse; } th, td { text-align: left; vertical-align: top; padding: 9px 6px; border-bottom: 1px solid #e2e8f0; overflow-wrap: anywhere; }
        .table { overflow-x: auto; } .muted { color: #64748b; } .warning { color: #9a3412; } a { color: #0369a1; overflow-wrap: anywhere; } .metrics { display: flex; flex-wrap: wrap; gap: 12px; } .metric { padding: 12px; background: #f1f5f9; border-radius: 8px; flex: 1 1 150px; } .value { display: block; font-size: 24px; font-weight: bold; }
        article { border-top: 1px solid #e2e8f0; padding-top: 12px; margin-top: 12px; }
        @media (max-width: 640px) { body { padding: 10px; } section, header { padding: 15px; } }
        @media print { body { background: white; padding: 0; } section { break-inside: avoid; } }
    </style>
</head>
<body>
@php
    $label = static fn (string $key): string => __('news_media_intel.report.'.$key);
    $summary = $report['summary'] ?? [];
    $visibility = $report['visibility'] ?? [];
    $safeUrl = static function (mixed $url): ?string {
        if (!is_string($url) || preg_match('/[\p{Cc}\s\\\\]/u', $url) !== 0) return null;
        $parts = parse_url($url);
        return is_array($parts) && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            && !empty($parts['host']) && !isset($parts['user']) && !isset($parts['pass']) ? $url : null;
    };
@endphp
<main>
    <header>
        <h1>{{ $label('title') }}</h1>
        <p>{{ $label('query') }}: {{ $report['query'] ?? '' }}</p>
        <p>{{ $label('checked_at') }}: {{ $report['checkedAt'] ?? '-' }}</p>
        <p>{{ $label('language') }}: {{ $report['options']['language'] ?? '-' }} · {{ $label('period') }}: {{ $label('periods.'.($report['options']['timeRange'] ?: 'all')) }}</p>
    </header>
    <section>
        <p class="muted">{{ $label('methodology') }}</p>
        @if($report['coverage']['partial'] ?? false)<p class="warning">{{ $label('partial') }}</p>@endif
        <div class="metrics">
            @foreach(['mentions', 'publishers', 'knownDates', 'undatedMentions'] as $key)
                <div class="metric"><span>{{ $label(['mentions'=>'mentions','publishers'=>'publishers','knownDates'=>'known_dates','undatedMentions'=>'unknown_dates'][$key]) }}</span><strong class="value">{{ $summary[$key] ?? 0 }}</strong></div>
            @endforeach
            <div class="metric">{{ $label('freshness') }}<strong class="value">{{ isset($summary['freshnessPercent']) ? $summary['freshnessPercent'].'%' : '—' }}</strong></div>
        </div>
    </section>
    <section>
        <h2>{{ $label('brand') }}</h2><p class="muted">{{ $label('brand_method') }}</p>
        <div class="table"><table><thead><tr><th>{{ $label('entity') }}</th><th>{{ $label('count') }}</th><th>{{ $label('share') }}</th><th>{{ $label('sentiment') }}</th></tr></thead><tbody>
            @forelse($report['brandComparison']['entities'] ?? [] as $entity)
                <tr><td>{{ $entity['name'] }}</td><td>{{ $entity['mentions'] }}</td><td>{{ $entity['share'] }}</td><td>{{ $entity['sentiment']['positive'] }} / {{ $entity['sentiment']['neutral'] }} / {{ $entity['sentiment']['negative'] }}</td></tr>
            @empty<tr><td colspan="4">{{ $label('no_data') }}</td></tr>@endforelse
        </tbody></table></div>
    </section>
    <section><h2>{{ $label('publishers') }}</h2><div class="table"><table><thead><tr><th>{{ $label('source') }}</th><th>{{ $label('count') }}</th><th>{{ $label('share') }}</th><th>{{ $label('engines') }}</th></tr></thead><tbody>
        @foreach($report['publishers'] ?? [] as $publisher)<tr><td>{{ $publisher['host'] }}</td><td>{{ $publisher['count'] }}</td><td>{{ $publisher['share'] }}</td><td>{{ implode(', ', $publisher['engines'] ?? []) }}</td></tr>@endforeach
    </tbody></table></div></section>
    <section><h2>{{ $label('topics') }}</h2><ul>@foreach($report['topics'] ?? [] as $topic)<li>{{ $topic['topic'] }} — {{ $topic['count'] }} ({{ $topic['share'] }}%)</li>@endforeach</ul>
        <h3>{{ $label('questions') }}</h3><ul>@foreach($report['questions'] ?? [] as $question)<li>{{ $question['question'] }} ({{ $question['count'] }})</li>@endforeach</ul>
        @foreach(['suggestions','corrections','answers'] as $key)
            @if(!empty($report[$key]))
                <h3>{{ $label($key) }}</h3>
                <ul>
                    @foreach($report[$key] as $text)
                        <li>{{ $text }}</li>
                    @endforeach
                </ul>
            @endif
        @endforeach
        @if(!empty($report['infoboxes']))
            <h3>{{ $label('infoboxes') }}</h3>
            @foreach($report['infoboxes'] as $box)
                <article><h3>{{ $box['title'] }}</h3><p>{{ $box['content'] }}</p>
                    @foreach($box['urls'] ?? [] as $link)
                        @if($safeUrl($link['url'] ?? null))
                            <p><a href="{{ $link['url'] }}" rel="noopener noreferrer">{{ $link['title'] ?: $link['url'] }}</a></p>
                        @endif
                    @endforeach
                </article>
            @endforeach
        @endif
    </section>
    <section><h2>{{ $label('opportunities') }}</h2><ul>
        @foreach($report['contentOpportunities'] ?? [] as $action)
            <li>{{ $label('actions.'.$action['code']) }}: {{ $action['params']['topic'] ?? $action['params']['question'] ?? $action['params']['brand'] ?? $action['params']['host'] ?? '' }}</li>
        @endforeach
    </ul></section>
    <section><h2>{{ $label('visibility') }}</h2><p class="muted">{{ $label('visibility_method') }}</p>
        @if(($report['coverage']['general']['status'] ?? '') === 'unavailable')
            <p class="warning">{{ $label('unavailable') }}</p>
        @else
        <p>{{ $label('domain') }}: {{ $visibility['domain'] ?: '—' }} · {{ $label('domain_matches') }}: {{ $visibility['domainMatches'] ?? 0 }} · {{ $label('best_position') }}: {{ $visibility['bestObservedPosition'] ?? '—' }}</p>
        <ul>@foreach($visibility['pages'] ?? [] as $page)<li>{{ $page['position'] ?? '—' }}. @if($safeUrl($page['url'] ?? null))<a href="{{ $page['url'] }}" rel="noopener noreferrer">{{ $page['title'] }}</a>@else{{ $page['title'] }}@endif</li>@endforeach</ul>
        @endif
    </section>
    <section><h2>{{ $label('coverage') }}</h2>
        @foreach(['general','news'] as $category)@php($coverage = $report['coverage'][$category] ?? [])
            <h3>{{ $label($category) }}</h3>
            <p>{{ $label('pages') }}: {{ $coverage['pagesLoaded'] ?? 0 }} / {{ $coverage['pagesRequested'] ?? 0 }} · {{ $label('engines') }}: {{ implode(', ', $coverage['engines'] ?? []) }}</p>
            @if(($coverage['status'] ?? '') === 'unavailable')<p class="warning">{{ $label('unavailable') }}</p>@elseif($coverage['truncated'] ?? false)<p class="warning">{{ $label('limited') }}</p>@endif
            @if(!empty($coverage['unresponsiveEngines']))<p class="warning">{{ $label('unresponsive') }}: {{ implode(', ', array_column($coverage['unresponsiveEngines'], 'name')) }}</p>@endif
        @endforeach
    </section>
    <section><h2>{{ $label('timeline') }}</h2><ul>@foreach($report['timeline'] ?? [] as $point)<li>{{ $point['date'] }}: {{ $point['mentions'] }}</li>@endforeach</ul></section>
    <section><h2>{{ $label('documents') }}</h2>
        @foreach($report['mentions'] ?? [] as $mention)
            <article><h3>{{ $mention['title'] }}</h3><p>{{ $mention['snippet'] }}</p><p class="muted">{{ $mention['publisher'] ?? '' }} · {{ $mention['publishedAt'] ?: $label('no_data') }} · {{ implode(', ', $mention['engines'] ?? []) }}</p>
                @if($safeUrl($mention['link'] ?? null))<a href="{{ $mention['link'] }}" rel="noopener noreferrer">{{ $mention['link'] }}</a>@endif
            </article>
        @endforeach
    </section>
</main>
</body>
</html>
