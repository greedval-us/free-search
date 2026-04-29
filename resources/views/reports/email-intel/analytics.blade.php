<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Email Intel Report</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; font-family: "DejaVu Sans", sans-serif; color: #0f172a; background: #f8fafc; line-height: 1.45; }
        .container { max-width: 1120px; margin: 0 auto; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; margin-bottom: 16px; overflow: hidden; }
        .header { padding: 20px 22px; background: linear-gradient(135deg, #0f172a, #164e63 55%, #0891b2); color: #fff; }
        .header h1 { margin: 0 0 6px; font-size: 22px; }
        .meta { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
        .chip { border-radius: 999px; border: 1px solid rgba(255,255,255,.25); padding: 4px 10px; font-size: 11px; }
        .body { padding: 16px 18px; }
        h2 { font-size: 14px; margin: 0 0 12px; }
        .grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
        .metric { border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; background: #f8fafc; }
        .label { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: .05em; }
        .value { margin-top: 6px; font-size: 22px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; border-bottom: 1px solid #e2e8f0; padding: 8px 6px; font-size: 12px; vertical-align: top; overflow-wrap: anywhere; }
        th { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: .05em; }
        .muted { color: #64748b; }
        @media (max-width: 960px) { .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 640px) { .grid { grid-template-columns: repeat(1, minmax(0, 1fr)); } }
    </style>
</head>
<body>
@php
    $reportLocale = ($locale ?? 'en') === 'ru' ? 'ru' : 'en';
    $tr = [
        'en' => [
            'title' => 'Email Intel Report',
            'generatedAt' => 'Generated at',
            'risk' => 'Risk',
            'domain' => 'Domain',
            'scores' => 'Scores',
            'overall' => 'Overall',
            'mailSecurity' => 'Mail security',
            'domainHealth' => 'Domain health',
            'identityConfidence' => 'Identity confidence',
            'providerSecurity' => 'Provider and Mail Security',
            'provider' => 'Provider',
            'spfStrictness' => 'SPF strictness',
            'dmarcPolicy' => 'DMARC policy',
            'signals' => 'Signals',
            'type' => 'Type',
            'level' => 'Level',
            'message' => 'Message',
            'noSignals' => 'No signals.',
            'recommendations' => 'Recommendations',
            'key' => 'Key',
            'priority' => 'Priority',
            'impact' => 'Impact',
            'noRecommendations' => 'No recommendations.',
            'riskBreakdown' => 'Risk breakdown',
            'points' => 'Points',
            'spfIncludes' => 'SPF includes',
            'includeDomain' => 'Include domain',
            'resolved' => 'Resolved',
            'dmarcReports' => 'DMARC reports',
            'mailbox' => 'Mailbox',
            'external' => 'External',
            'webSnapshot' => 'Domain web snapshot',
            'status' => 'Status',
            'duration' => 'Duration',
            'similarDomains' => 'Similar domains',
            'reason' => 'Reason',
            'graphSummary' => 'Entity graph summary',
            'nodes' => 'Nodes',
            'edges' => 'Edges',
            'deliverability' => 'Deliverability',
            'deliverabilityHints' => 'Deliverability hints',
            'yes' => 'yes',
            'no' => 'no',
            'recommendation' => [
                'configure_mx' => 'Configure MX records for the domain.',
                'harden_spf' => 'Harden SPF policy and avoid permissive all mechanisms.',
                'enforce_dmarc' => 'Move DMARC toward quarantine or reject after monitoring.',
                'review_disposable' => 'Review disposable mailbox usage manually.',
                'verify_role_account' => 'Verify whether this role/shared mailbox is expected.',
                'review_dmarc_reporting' => 'Review whether external DMARC reporting destinations are expected and authorized.',
                'maintain_posture' => 'Current mail posture looks healthy; keep monitoring it.',
            ],
        ],
        'ru' => [
            'title' => 'Отчет Email Intel',
            'generatedAt' => 'Сформирован',
            'risk' => 'Риск',
            'domain' => 'Домен',
            'scores' => 'Скоры',
            'overall' => 'Общий',
            'mailSecurity' => 'Защита почты',
            'domainHealth' => 'Здоровье домена',
            'identityConfidence' => 'Уверенность идентичности',
            'providerSecurity' => 'Провайдер и защита почты',
            'provider' => 'Провайдер',
            'spfStrictness' => 'Строгость SPF',
            'dmarcPolicy' => 'Политика DMARC',
            'signals' => 'Сигналы',
            'type' => 'Тип',
            'level' => 'Уровень',
            'message' => 'Сообщение',
            'noSignals' => 'Сигналов нет.',
            'recommendations' => 'Рекомендации',
            'key' => 'Рекомендация',
            'priority' => 'Приоритет',
            'impact' => 'Эффект',
            'noRecommendations' => 'Рекомендаций нет.',
            'riskBreakdown' => 'Расшифровка риска',
            'points' => 'Баллы',
            'spfIncludes' => 'SPF include',
            'includeDomain' => 'Include-домен',
            'resolved' => 'Найдено',
            'dmarcReports' => 'DMARC отчеты',
            'mailbox' => 'Ящик',
            'external' => 'Внешний',
            'webSnapshot' => 'Web snapshot домена',
            'status' => 'Статус',
            'duration' => 'Время',
            'similarDomains' => 'Похожие домены',
            'reason' => 'Причина',
            'graphSummary' => 'Сводка графа',
            'nodes' => 'Узлы',
            'edges' => 'Связи',
            'deliverability' => 'Доставляемость',
            'deliverabilityHints' => 'Подсказки доставляемости',
            'yes' => 'да',
            'no' => 'нет',
            'recommendation' => [
                'configure_mx' => 'Настроить MX-записи домена.',
                'harden_spf' => 'Усилить SPF и избегать разрешающих all-механизмов.',
                'enforce_dmarc' => 'Перевести DMARC к quarantine или reject после мониторинга.',
                'review_disposable' => 'Вручную проверить использование одноразовой почты.',
                'verify_role_account' => 'Проверить, ожидаем ли этот общий или ролевой ящик.',
                'review_dmarc_reporting' => 'Проверьте, что внешние DMARC-адреса отчетности ожидаемы и авторизованы.',
                'maintain_posture' => 'Текущая почтовая конфигурация выглядит здоровой; продолжайте мониторинг.',
            ],
        ],
    ][$reportLocale];

    $target = $report['target'] ?? [];
    $dns = $report['dns'] ?? [];
    $analytics = $report['analytics'] ?? [];
    $scores = $analytics['scores'] ?? [];
    $provider = $analytics['provider'] ?? [];
    $spf = $analytics['spf'] ?? [];
    $spfExpandedIncludes = is_array($analytics['spfExpandedIncludes'] ?? null) ? $analytics['spfExpandedIncludes'] : [];
    $dmarc = $analytics['dmarc'] ?? [];
    $dmarcReports = $analytics['dmarcReports'] ?? [];
    $dmarcReportDestinations = is_array($dmarcReports['destinations'] ?? null) ? $dmarcReports['destinations'] : [];
    $riskBreakdown = $analytics['riskBreakdown'] ?? [];
    $riskItems = is_array($riskBreakdown['items'] ?? null) ? $riskBreakdown['items'] : [];
    $deliverability = $analytics['deliverability'] ?? [];
    $deliverabilityHints = is_array($deliverability['hints'] ?? null) ? $deliverability['hints'] : [];
    $webSnapshot = $analytics['webSnapshot'] ?? [];
    $similarDomains = is_array($analytics['similarDomains'] ?? null) ? $analytics['similarDomains'] : [];
    $graph = $analytics['graph'] ?? [];
    $recommendations = is_array($analytics['recommendations'] ?? null) ? $analytics['recommendations'] : [];
    $signals = is_array($report['signals'] ?? null) ? $report['signals'] : [];
@endphp
<main class="container">
    <section class="card">
        <header class="header">
            <h1>{{ $tr['title'] }}</h1>
            <p>{{ $tr['generatedAt'] }} {{ $generatedAt }}</p>
            <div class="meta">
                <span class="chip">{{ $target['email'] ?? '-' }}</span>
                <span class="chip">{{ $tr['risk'] }}: {{ $report['riskScore'] ?? 0 }} / {{ $report['riskLevel'] ?? '-' }}</span>
                <span class="chip">{{ $tr['domain'] }}: {{ $target['domain'] ?? '-' }}</span>
            </div>
        </header>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['scores'] }}</h2>
            <div class="grid">
                <article class="metric"><div class="label">{{ $tr['overall'] }}</div><div class="value">{{ $scores['overall'] ?? 0 }}</div></article>
                <article class="metric"><div class="label">{{ $tr['mailSecurity'] }}</div><div class="value">{{ $scores['mailSecurity'] ?? 0 }}</div></article>
                <article class="metric"><div class="label">{{ $tr['domainHealth'] }}</div><div class="value">{{ $scores['domainHealth'] ?? 0 }}</div></article>
                <article class="metric"><div class="label">{{ $tr['identityConfidence'] }}</div><div class="value">{{ $scores['identityConfidence'] ?? 0 }}</div></article>
                <article class="metric"><div class="label">{{ $tr['deliverability'] }}</div><div class="value">{{ $deliverability['score'] ?? 0 }}</div></article>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['providerSecurity'] }}</h2>
            <table>
                <tbody>
                    <tr><th>{{ $tr['provider'] }}</th><td>{{ $provider['name'] ?? '-' }} ({{ $provider['confidence'] ?? 0 }}%)</td></tr>
                    <tr><th>MX</th><td>{{ collect($dns['mx'] ?? [])->map(fn ($record) => ($record['host'] ?? '-') . ' (' . ($record['priority'] ?? 0) . ')')->join(', ') ?: '-' }}</td></tr>
                    <tr><th>SPF</th><td>{{ $spf['record'] ?? '-' }}</td></tr>
                    <tr><th>{{ $tr['spfStrictness'] }}</th><td>{{ $spf['strictness'] ?? '-' }}</td></tr>
                    <tr><th>DMARC</th><td>{{ $dmarc['record'] ?? '-' }}</td></tr>
                    <tr><th>{{ $tr['dmarcPolicy'] }}</th><td>{{ $dmarc['policy'] ?? '-' }} / {{ $dmarc['strength'] ?? '-' }}</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['signals'] }}</h2>
            <table>
                <thead><tr><th>{{ $tr['type'] }}</th><th>{{ $tr['level'] }}</th><th>{{ $tr['message'] }}</th></tr></thead>
                <tbody>
                    @forelse($signals as $signal)
                        <tr><td>{{ $signal['type'] ?? '-' }}</td><td>{{ $signal['level'] ?? '-' }}</td><td>{{ $signal['message'] ?? '-' }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="muted">{{ $tr['noSignals'] }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['riskBreakdown'] }}</h2>
            <table>
                <thead><tr><th>{{ $tr['type'] }}</th><th>{{ $tr['level'] }}</th><th>{{ $tr['points'] }}</th><th>{{ $tr['message'] }}</th></tr></thead>
                <tbody>
                    @forelse($riskItems as $item)
                        <tr><td>{{ $item['type'] ?? '-' }}</td><td>{{ $item['level'] ?? '-' }}</td><td>+{{ $item['points'] ?? 0 }}</td><td>{{ $item['message'] ?? '-' }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="muted">-</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['deliverabilityHints'] }}</h2>
            <table>
                <thead><tr><th>{{ $tr['key'] }}</th><th>{{ $tr['level'] }}</th><th>{{ $tr['status'] }}</th><th>{{ $tr['message'] }}</th></tr></thead>
                <tbody>
                    @forelse($deliverabilityHints as $hint)
                        <tr><td>{{ $hint['key'] ?? '-' }}</td><td>{{ $hint['level'] ?? '-' }}</td><td>{{ ($hint['passed'] ?? false) ? $tr['yes'] : $tr['no'] }}</td><td>{{ $hint['message'] ?? '-' }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="muted">-</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['spfIncludes'] }}</h2>
            <table>
                <thead><tr><th>{{ $tr['includeDomain'] }}</th><th>{{ $tr['resolved'] }}</th><th>{{ $tr['spfStrictness'] }}</th><th>SPF</th></tr></thead>
                <tbody>
                    @forelse($spfExpandedIncludes as $include)
                        <tr><td>{{ $include['domain'] ?? '-' }}</td><td>{{ ($include['resolved'] ?? false) ? $tr['yes'] : $tr['no'] }}</td><td>{{ $include['strictness'] ?? '-' }}</td><td>{{ $include['record'] ?? '-' }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="muted">-</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['dmarcReports'] }}</h2>
            <table>
                <thead><tr><th>{{ $tr['type'] }}</th><th>{{ $tr['mailbox'] }}</th><th>{{ $tr['domain'] }}</th><th>{{ $tr['external'] }}</th></tr></thead>
                <tbody>
                    @forelse($dmarcReportDestinations as $destination)
                        <tr><td>{{ $destination['kind'] ?? '-' }}</td><td>{{ $destination['mailbox'] ?? '-' }}</td><td>{{ $destination['domain'] ?? '-' }}</td><td>{{ ($destination['external'] ?? false) ? $tr['yes'] : $tr['no'] }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="muted">-</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['recommendations'] }}</h2>
            <table>
                <thead><tr><th>{{ $tr['key'] }}</th><th>{{ $tr['priority'] }}</th><th>{{ $tr['impact'] }}</th></tr></thead>
                <tbody>
                    @forelse($recommendations as $item)
                        @php $recommendationKey = (string) ($item['key'] ?? ''); @endphp
                        <tr><td>{{ $tr['recommendation'][$recommendationKey] ?? $recommendationKey ?: '-' }}</td><td>{{ $item['priority'] ?? '-' }}</td><td>+{{ $item['impact'] ?? 0 }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="muted">{{ $tr['noRecommendations'] }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['webSnapshot'] }}</h2>
            <table>
                <tbody>
                    <tr><th>URL</th><td>{{ $webSnapshot['url'] ?? '-' }}</td></tr>
                    <tr><th>{{ $tr['status'] }}</th><td>{{ $webSnapshot['status'] ?? '-' }}</td></tr>
                    <tr><th>{{ $tr['duration'] }}</th><td>{{ $webSnapshot['durationMs'] ?? 0 }} ms</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['similarDomains'] }}</h2>
            <table>
                <thead><tr><th>{{ $tr['domain'] }}</th><th>{{ $tr['reason'] }}</th></tr></thead>
                <tbody>
                    @forelse($similarDomains as $item)
                        <tr><td>{{ $item['domain'] ?? '-' }}</td><td>{{ $item['reason'] ?? '-' }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="muted">-</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['graphSummary'] }}</h2>
            <div class="grid">
                <article class="metric"><div class="label">{{ $tr['nodes'] }}</div><div class="value">{{ count($graph['nodes'] ?? []) }}</div></article>
                <article class="metric"><div class="label">{{ $tr['edges'] }}</div><div class="value">{{ count($graph['edges'] ?? []) }}</div></article>
            </div>
        </div>
    </section>
</main>
</body>
</html>
