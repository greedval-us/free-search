<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Site Intel Analytics Report</title>
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
        ul { margin: 0; padding-left: 16px; }
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
            'title' => 'Site Intel Analytics Report',
            'generatedAt' => 'Generated at',
            'target' => 'Target',
            'domain' => 'Domain',
            'checkedAt' => 'Checked at',
            'overview' => 'Overview',
            'overallScore' => 'Overall score',
            'healthScore' => 'Site Health score',
            'domainRiskScore' => 'Domain risk score',
            'domainSafetyScore' => 'Domain safety score',
            'headersCoverage' => 'Headers coverage',
            'redirects' => 'Redirects',
            'daysToDomainExpiry' => 'Days to domain expiry',
            'dnsStats' => 'DNS stats',
            'riskSignals' => 'Risk signals',
            'strengthSignals' => 'Strength signals',
            'recommendations' => 'Recommendations',
            'none' => 'No items',
            'additional' => 'Additional snapshot metrics',
            'httpFinalStatus' => 'HTTP final status',
            'totalResponseTime' => 'Total response time',
            'sslDaysRemaining' => 'SSL days remaining',
            'whois' => 'WHOIS',
            'domainAge' => 'Domain age (days)',
            'emailSecurity' => 'Email security (SPF/DMARC)',
            'available' => 'Available',
            'unavailable' => 'Unavailable',
            'yes' => 'Yes',
            'no' => 'No',
        ],
        'ru' => [
            'title' => 'Отчёт Site Intel Analytics',
            'generatedAt' => 'Сформирован',
            'target' => 'Цель',
            'domain' => 'Домен',
            'checkedAt' => 'Проверено',
            'overview' => 'Сводка',
            'overallScore' => 'Общий балл',
            'healthScore' => 'Балл Site Health',
            'domainRiskScore' => 'Балл доменного риска',
            'domainSafetyScore' => 'Оценка безопасности домена',
            'headersCoverage' => 'Покрытие заголовков',
            'redirects' => 'Редиректы',
            'daysToDomainExpiry' => 'Дней до истечения домена',
            'dnsStats' => 'DNS-статистика',
            'riskSignals' => 'Сигналы риска',
            'strengthSignals' => 'Позитивные сигналы',
            'recommendations' => 'Рекомендации',
            'none' => 'Нет данных',
            'additional' => 'Дополнительные метрики снимка',
            'httpFinalStatus' => 'Финальный HTTP-статус',
            'totalResponseTime' => 'Суммарное время ответа',
            'sslDaysRemaining' => 'Дней до истечения SSL',
            'whois' => 'WHOIS',
            'domainAge' => 'Возраст домена (дни)',
            'emailSecurity' => 'Почтовая защита (SPF/DMARC)',
            'available' => 'Доступно',
            'unavailable' => 'Недоступно',
            'yes' => 'Да',
            'no' => 'Нет',
        ],
    ][$reportLocale];

    $riskSignalLabels = [
        'en' => [
            'no_dns_records' => 'No DNS A/AAAA records',
            'unreachable' => 'Target is unreachable',
            'http_error_status' => 'Final HTTP status is an error',
            'final_url_not_https' => 'Final URL is not HTTPS',
            'too_many_redirects' => 'Too many redirects',
            'ssl_unavailable' => 'SSL unavailable',
            'ssl_expired' => 'SSL certificate expired',
            'ssl_expiring_soon' => 'SSL certificate expires soon',
            'no_a_or_aaaa_records' => 'No A/AAAA records',
            'no_ns_records' => 'No NS records',
            'no_mx_records' => 'No MX records',
            'missing_spf' => 'SPF record missing',
            'missing_dmarc' => 'DMARC record missing',
            'whois_unavailable' => 'WHOIS unavailable',
            'domain_expired' => 'Domain expired',
            'domain_expires_soon' => 'Domain expires within 30 days',
            'domain_expires_in_90_days' => 'Domain expires within 90 days',
            'missing_strict_transport_security' => 'Missing HSTS',
            'missing_content_security_policy' => 'Missing CSP',
            'missing_x_frame_options' => 'Missing X-Frame-Options',
            'missing_x_content_type_options' => 'Missing X-Content-Type-Options',
            'missing_referrer_policy' => 'Missing Referrer-Policy',
            'missing_permissions_policy' => 'Missing Permissions-Policy',
        ],
        'ru' => [
            'no_dns_records' => 'Нет DNS A/AAAA записей',
            'unreachable' => 'Цель недоступна',
            'http_error_status' => 'Финальный HTTP-статус содержит ошибку',
            'final_url_not_https' => 'Финальный URL не использует HTTPS',
            'too_many_redirects' => 'Слишком много редиректов',
            'ssl_unavailable' => 'SSL недоступен',
            'ssl_expired' => 'SSL-сертификат истёк',
            'ssl_expiring_soon' => 'SSL-сертификат скоро истечёт',
            'no_a_or_aaaa_records' => 'Нет A/AAAA записей',
            'no_ns_records' => 'Нет NS записей',
            'no_mx_records' => 'Нет MX записей',
            'missing_spf' => 'Отсутствует SPF-запись',
            'missing_dmarc' => 'Отсутствует DMARC-запись',
            'whois_unavailable' => 'WHOIS недоступен',
            'domain_expired' => 'Домен истёк',
            'domain_expires_soon' => 'Домен истекает в течение 30 дней',
            'domain_expires_in_90_days' => 'Домен истекает в течение 90 дней',
            'missing_strict_transport_security' => 'Отсутствует HSTS',
            'missing_content_security_policy' => 'Отсутствует CSP',
            'missing_x_frame_options' => 'Отсутствует X-Frame-Options',
            'missing_x_content_type_options' => 'Отсутствует X-Content-Type-Options',
            'missing_referrer_policy' => 'Отсутствует Referrer-Policy',
            'missing_permissions_policy' => 'Отсутствует Permissions-Policy',
        ],
    ][$reportLocale];

    $strengthSignalLabels = [
        'en' => [
            'https_enforced' => 'HTTPS is enforced on final URL',
            'ssl_valid' => 'SSL certificate is valid and not expiring soon',
            'spf_present' => 'SPF record is configured',
            'dmarc_present' => 'DMARC record is configured',
            'whois_available' => 'WHOIS data is available',
            'headers_coverage_good' => 'Good security header coverage',
        ],
        'ru' => [
            'https_enforced' => 'HTTPS включён на финальном URL',
            'ssl_valid' => 'SSL-сертификат валиден и не скоро истечёт',
            'spf_present' => 'SPF-запись настроена',
            'dmarc_present' => 'DMARC-запись настроена',
            'whois_available' => 'WHOIS-данные доступны',
            'headers_coverage_good' => 'Хорошее покрытие security-заголовков',
        ],
    ][$reportLocale];

    $recommendationLabels = [
        'en' => [
            'improve_security_headers' => 'Improve security headers (CSP, HSTS, X-Frame-Options, etc.)',
            'enforce_https' => 'Enforce HTTPS and proper redirects',
            'renew_ssl_certificate' => 'Renew or reissue SSL certificate',
            'configure_email_security' => 'Configure SPF and DMARC for domain email security',
            'review_dns_configuration' => 'Review DNS configuration (A/AAAA/NS/MX records)',
            'renew_domain_early' => 'Renew domain early to avoid expiration risks',
            'check_whois_visibility' => 'Check WHOIS visibility and correctness',
            'maintain_current_posture' => 'Current posture looks healthy; keep it maintained',
        ],
        'ru' => [
            'improve_security_headers' => 'Улучшите security-заголовки (CSP, HSTS, X-Frame-Options и т.д.)',
            'enforce_https' => 'Включите обязательный HTTPS и корректные редиректы',
            'renew_ssl_certificate' => 'Продлите или перевыпустите SSL-сертификат',
            'configure_email_security' => 'Настройте SPF и DMARC для почтовой безопасности домена',
            'review_dns_configuration' => 'Проверьте DNS-конфигурацию (записи A/AAAA/NS/MX)',
            'renew_domain_early' => 'Продлите домен заранее, чтобы избежать рисков истечения',
            'check_whois_visibility' => 'Проверьте доступность и корректность WHOIS-данных',
            'maintain_current_posture' => 'Текущее состояние выглядит стабильно, продолжайте поддерживать его',
        ],
    ][$reportLocale];

    $humanizeKey = static function (string $key): string {
        $value = str_replace('_', ' ', trim($key));

        return ucfirst($value);
    };

    $riskSignalLabel = static function (string $key) use ($riskSignalLabels, $humanizeKey): string {
        return $riskSignalLabels[$key] ?? $humanizeKey($key);
    };
    $strengthSignalLabel = static function (string $key) use ($strengthSignalLabels, $humanizeKey): string {
        return $strengthSignalLabels[$key] ?? $humanizeKey($key);
    };
    $recommendationLabel = static function (string $key) use ($recommendationLabels, $humanizeKey): string {
        return $recommendationLabels[$key] ?? $humanizeKey($key);
    };

    $overview = $report['overview'] ?? [];
    $target = $report['target'] ?? [];
    $dnsStats = $overview['dnsStats'] ?? [];
    $headersCoverage = $overview['headersCoverage'] ?? [];
    $riskSignals = is_array($overview['signals']['risks'] ?? null) ? $overview['signals']['risks'] : [];
    $strengthSignals = is_array($overview['signals']['strengths'] ?? null) ? $overview['signals']['strengths'] : [];
    $recommendations = is_array($overview['recommendations'] ?? null) ? $overview['recommendations'] : [];
    $ssl = $report['siteHealth']['ssl'] ?? [];
    $httpChain = is_array($report['siteHealth']['http']['chain'] ?? null) ? $report['siteHealth']['http']['chain'] : [];
    $totalResponseTime = array_sum(array_map(
        static fn ($step) => (int) ($step['responseTimeMs'] ?? 0),
        $httpChain
    ));
    $whois = $report['domainLite']['whois'] ?? [];
    $emailSecurity = $report['domainLite']['dns']['emailSecurity'] ?? [];
    $domainAgeDays = null;
    if (is_string($whois['createdAt'] ?? null) && trim((string) $whois['createdAt']) !== '') {
        try {
            $domainAgeDays = \Carbon\Carbon::parse($whois['createdAt'])->diffInDays(\Carbon\Carbon::now(), false);
        } catch (\Throwable) {
            $domainAgeDays = null;
        }
    }
@endphp

<main class="container">
    <section class="card">
        <header class="header">
            <h1>{{ $tr['title'] }}</h1>
            <p>{{ $tr['generatedAt'] }} {{ $generatedAt }}</p>
            <div class="meta">
                <span class="chip">{{ $tr['target'] }}: {{ $target['url'] ?? '-' }}</span>
                <span class="chip">{{ $tr['domain'] }}: {{ $target['domain'] ?? '-' }}</span>
                <span class="chip">{{ $tr['checkedAt'] }}: {{ $report['checkedAt'] ?? '-' }}</span>
            </div>
        </header>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['overview'] }}</h2>
            <div class="grid">
                <article class="metric">
                    <div class="label">{{ $tr['overallScore'] }}</div>
                    <div class="value">{{ $overview['score']['value'] ?? 0 }}</div>
                </article>
                <article class="metric">
                    <div class="label">{{ $tr['healthScore'] }}</div>
                    <div class="value">{{ $overview['healthScore'] ?? 0 }}</div>
                </article>
                <article class="metric">
                    <div class="label">{{ $tr['domainRiskScore'] }}</div>
                    <div class="value">{{ $overview['domainRiskScore'] ?? 0 }}</div>
                </article>
                <article class="metric">
                    <div class="label">{{ $tr['domainSafetyScore'] }}</div>
                    <div class="value">{{ max(0, 100 - (int) ($overview['domainRiskScore'] ?? 0)) }}</div>
                </article>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['additional'] }}</h2>
            <table>
                <tbody>
                <tr>
                    <th>{{ $tr['headersCoverage'] }}</th>
                    <td>{{ $headersCoverage['present'] ?? 0 }}/{{ $headersCoverage['total'] ?? 0 }} ({{ $headersCoverage['percent'] ?? 0 }}%)</td>
                </tr>
                <tr>
                    <th>{{ $tr['redirects'] }}</th>
                    <td>{{ $overview['redirects'] ?? 0 }}</td>
                </tr>
                <tr>
                    <th>{{ $tr['daysToDomainExpiry'] }}</th>
                    <td>{{ $overview['daysToDomainExpiry'] ?? '-' }}</td>
                </tr>
                <tr>
                    <th>{{ $tr['dnsStats'] }}</th>
                    <td>
                        A: {{ $dnsStats['a'] ?? 0 }},
                        AAAA: {{ $dnsStats['aaaa'] ?? 0 }},
                        NS: {{ $dnsStats['ns'] ?? 0 }},
                        MX: {{ $dnsStats['mx'] ?? 0 }}
                    </td>
                </tr>
                <tr>
                    <th>{{ $tr['httpFinalStatus'] }}</th>
                    <td>{{ $report['siteHealth']['http']['finalStatus'] ?? '-' }}</td>
                </tr>
                <tr>
                    <th>{{ $tr['totalResponseTime'] }}</th>
                    <td>{{ $totalResponseTime }} ms</td>
                </tr>
                <tr>
                    <th>{{ $tr['sslDaysRemaining'] }}</th>
                    <td>{{ $ssl['daysRemaining'] ?? '-' }}</td>
                </tr>
                <tr>
                    <th>{{ $tr['whois'] }}</th>
                    <td>{{ ($whois['available'] ?? false) ? $tr['available'] : $tr['unavailable'] }}</td>
                </tr>
                <tr>
                    <th>{{ $tr['domainAge'] }}</th>
                    <td>{{ $domainAgeDays ?? '-' }}</td>
                </tr>
                <tr>
                    <th>{{ $tr['emailSecurity'] }}</th>
                    <td>
                        SPF: {{ ($emailSecurity['hasSpf'] ?? false) ? $tr['yes'] : $tr['no'] }},
                        DMARC: {{ ($emailSecurity['hasDmarc'] ?? false) ? $tr['yes'] : $tr['no'] }}
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['riskSignals'] }}</h2>
            @if($riskSignals === [])
                <p class="muted">{{ $tr['none'] }}</p>
            @else
                <ul>
                    @foreach($riskSignals as $item)
                        <li>{{ $riskSignalLabel((string) $item) }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['strengthSignals'] }}</h2>
            @if($strengthSignals === [])
                <p class="muted">{{ $tr['none'] }}</p>
            @else
                <ul>
                    @foreach($strengthSignals as $item)
                        <li>{{ $strengthSignalLabel((string) $item) }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['recommendations'] }}</h2>
            @if($recommendations === [])
                <p class="muted">{{ $tr['none'] }}</p>
            @else
                <ul>
                    @foreach($recommendations as $item)
                        <li>{{ $recommendationLabel((string) $item) }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>
</main>
</body>
</html>
