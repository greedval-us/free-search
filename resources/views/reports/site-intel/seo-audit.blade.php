<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SEO Audit Report</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; font-family: "DejaVu Sans", sans-serif; color: #0f172a; background: #f8fafc; line-height: 1.45; }
        .container { max-width: 1120px; margin: 0 auto; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; margin-bottom: 16px; overflow: hidden; }
        .header { padding: 20px 22px; background: linear-gradient(135deg, #0f172a, #0369a1 55%, #0891b2); color: #fff; }
        .header h1 { margin: 0 0 8px; font-size: 22px; }
        .meta { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
        .chip { border-radius: 999px; border: 1px solid rgba(255,255,255,.25); padding: 4px 10px; font-size: 11px; white-space: nowrap; }
        .body { padding: 16px 18px; }
        .grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
        .metric { border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; background: #f8fafc; }
        .label { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: .05em; }
        .value { margin-top: 6px; font-size: 20px; font-weight: 700; }
        h2 { margin: 0 0 10px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; border-bottom: 1px solid #e2e8f0; padding: 8px 6px; font-size: 12px; vertical-align: top; }
        th { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: .05em; }
        .muted { color: #64748b; }
        ul { margin: 0; padding-left: 16px; }
    </style>
</head>
<body>
@php
    $isRu = ($locale ?? 'en') === 'ru';

    $tr = $isRu ? [
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
        'status' => 'Статус',
        'titleCol' => 'Title',
        'h1' => 'H1',
        'indexable' => 'Индексируется',
        'yes' => 'Да',
        'no' => 'Нет',
        'url' => 'URL',
        'missing' => 'Без canonical',
        'crossDomain' => 'Междоменный canonical',
        'invalid' => 'Невалидный canonical',
        'sampled' => 'Проверено URL',
        'non200' => 'URL с не-200',
        'pagesWithHreflang' => 'Страниц с hreflang',
        'missingSelfReference' => 'Без self-reference',
        'duplicateLangTags' => 'Дубликаты lang-тегов',
        'metaTags' => 'Метатеги',
        'indexabilityTitle' => 'Индексируемость',
        'headingsTitle' => 'Заголовки',
        'linksTitle' => 'Ссылки',
        'performanceTitle' => 'Производительность',
        'crawlFilesTitle' => 'Файлы для краулинга',
        'securityTitle' => 'Безопасность',
        'technicalFlags' => 'Технические флаги',
        'contentQuality' => 'Качество контента',
        'accessibility' => 'Доступность',
        'linkGraph' => 'Граф ссылок',
        'international' => 'Международное SEO',
        'crawlBudget' => 'Краулинговый бюджет',
        'detectedSignals' => 'Обнаруженные сигналы',
    ] : [
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
        'status' => 'Status',
        'titleCol' => 'Title',
        'h1' => 'H1',
        'indexable' => 'Indexable',
        'yes' => 'Yes',
        'no' => 'No',
        'url' => 'URL',
        'missing' => 'Missing canonical',
        'crossDomain' => 'Cross-domain canonical',
        'invalid' => 'Invalid canonical',
        'sampled' => 'Sampled URLs',
        'non200' => 'Non-200 URLs',
        'pagesWithHreflang' => 'Pages with hreflang',
        'missingSelfReference' => 'Missing self-reference',
        'duplicateLangTags' => 'Duplicate lang tags',
        'metaTags' => 'Meta tags',
        'indexabilityTitle' => 'Indexability',
        'headingsTitle' => 'Headings',
        'linksTitle' => 'Links',
        'performanceTitle' => 'Performance',
        'crawlFilesTitle' => 'Crawling files',
        'securityTitle' => 'Security',
        'technicalFlags' => 'Technical flags',
        'contentQuality' => 'Content quality',
        'accessibility' => 'Accessibility',
        'linkGraph' => 'Link graph',
        'international' => 'International SEO',
        'crawlBudget' => 'Crawl budget',
        'detectedSignals' => 'Detected signals',
    ];

    $priorityLabels = $isRu
        ? ['critical' => 'КРИТИЧЕСКИЙ', 'medium' => 'СРЕДНИЙ', 'low' => 'НИЗКИЙ']
        : ['critical' => 'CRITICAL', 'medium' => 'MEDIUM', 'low' => 'LOW'];

    $recommendationLabels = $isRu ? [
        'enable_indexing' => 'Разрешите индексацию: уберите noindex из meta robots / X-Robots-Tag.',
        'enable_https' => 'Переведите страницу на HTTPS.',
        'improve_title_length' => 'Оптимизируйте длину title до 15-65 символов.',
        'improve_description_length' => 'Оптимизируйте длину description до 50-170 символов.',
        'fix_h1_count' => 'Оставьте ровно один H1 на странице.',
        'add_robots_txt' => 'Добавьте robots.txt с корректными директивами для краулеров.',
        'add_sitemap' => 'Добавьте sitemap.xml и укажите его в robots.txt.',
        'improve_ttfb' => 'Снизьте время ответа сервера (TTFB).',
        'reduce_page_size' => 'Уменьшите вес HTML/ресурсов страницы.',
        'remove_mixed_content' => 'Уберите mixed content: все ресурсы должны грузиться по HTTPS.',
        'maintain_current_setup' => 'Критичных проблем не найдено. Поддерживайте текущий уровень оптимизации.',
        'deduplicate_titles' => 'Уберите дубли title на просканированных страницах.',
        'set_canonical_on_all_pages' => 'Проставьте canonical на всех индексируемых страницах.',
        'fix_hreflang_self_reference' => 'Добавьте self-reference в hreflang для переводных страниц.',
        'fix_sitemap_non_200_urls' => 'Удалите или исправьте URL в sitemap, которые возвращают не-200.',
        'add_viewport_meta' => 'Добавьте адаптивный viewport meta (width=device-width, initial-scale=1).',
        'fix_soft_404' => 'Возвращайте корректный статус 404/410 для страниц с отсутствующим контентом.',
        'reduce_render_blocking' => 'Снизьте количество CSS/JS, блокирующих рендер, и откладывайте некритичные скрипты.',
        'fix_pagination_rel_links' => 'Добавьте консистентные rel prev/next для страниц пагинации.',
        'fix_empty_anchor_text' => 'Замените пустые/общие anchor-тексты на описательные.',
        'add_image_alt_attributes' => 'Добавьте alt-атрибуты для информативных изображений.',
        'expand_thin_content' => 'Расширьте тонкий контент полезным тематическим текстом.',
        'improve_text_html_ratio' => 'Сократите служебную разметку и увеличьте долю полезного текста.',
        'improve_internal_linking' => 'Добавьте внутренние ссылки для снижения риска orphan-страниц.',
        'fix_html_structure' => 'Исправьте базовые проблемы HTML-структуры (doctype/head/body/иерархия заголовков).',
        'fix_hreflang_reciprocal_links' => 'Приведите hreflang-ссылки к взаимной схеме между переводными страницами.',
        'add_hreflang_x_default' => 'Добавьте x-default для мультиязычных групп страниц.',
        'verify_bot_access_in_logs' => 'Проверьте, что поисковые боты доступны к сайту и логи собираются.',
        'reduce_bot_5xx_errors' => 'Проанализируйте и снизьте 5xx-ошибки для ботов.',
    ] : [
        'enable_indexing' => 'Allow indexing: remove noindex from meta robots / X-Robots-Tag.',
        'enable_https' => 'Switch the page to HTTPS.',
        'improve_title_length' => 'Optimize title length to 15-65 characters.',
        'improve_description_length' => 'Optimize description length to 50-170 characters.',
        'fix_h1_count' => 'Keep exactly one H1 on the page.',
        'add_robots_txt' => 'Add robots.txt with correct crawler directives.',
        'add_sitemap' => 'Add sitemap.xml and declare it in robots.txt.',
        'improve_ttfb' => 'Reduce server response time (TTFB).',
        'reduce_page_size' => 'Reduce HTML/resources page size.',
        'remove_mixed_content' => 'Remove mixed content: all resources should load over HTTPS.',
        'maintain_current_setup' => 'No critical issues found. Maintain current optimization quality.',
        'deduplicate_titles' => 'Deduplicate page titles across crawled pages.',
        'set_canonical_on_all_pages' => 'Set canonical tag on every indexable page.',
        'fix_hreflang_self_reference' => 'Add self-referencing hreflang for translated pages.',
        'fix_sitemap_non_200_urls' => 'Remove or fix sitemap URLs returning non-200 responses.',
        'add_viewport_meta' => 'Add responsive viewport meta (width=device-width, initial-scale=1).',
        'fix_soft_404' => 'Return proper 404/410 status for not-found content pages.',
        'reduce_render_blocking' => 'Reduce render-blocking CSS/JS and defer non-critical scripts.',
        'fix_pagination_rel_links' => 'Add consistent rel prev/next signals for paginated pages.',
        'fix_empty_anchor_text' => 'Replace empty/generic anchor texts with descriptive link text.',
        'add_image_alt_attributes' => 'Add alt attributes to informative images.',
        'expand_thin_content' => 'Expand thin content with useful topic-relevant text.',
        'improve_text_html_ratio' => 'Reduce boilerplate markup and improve meaningful text ratio.',
        'improve_internal_linking' => 'Add internal links to reduce orphan-page risk.',
        'fix_html_structure' => 'Fix core HTML structure issues (doctype/head/body/heading hierarchy).',
        'fix_hreflang_reciprocal_links' => 'Ensure hreflang links are reciprocal between translated pages.',
        'add_hreflang_x_default' => 'Add x-default hreflang for international page sets.',
        'verify_bot_access_in_logs' => 'Verify that search bots can access the site and logs are collected.',
        'reduce_bot_5xx_errors' => 'Investigate and reduce 5xx responses seen by bots.',
    ];

    $signalLabels = $isRu ? [
        'title_length_out_of_range' => 'Длина title вне рекомендованного диапазона',
        'description_length_out_of_range' => 'Длина description вне рекомендованного диапазона',
        'invalid_h1_count' => 'На странице должен быть ровно один H1',
        'not_indexable' => 'Страница помечена как неиндексируемая',
        'robots_missing' => 'robots.txt отсутствует или недоступен',
        'sitemap_missing' => 'sitemap.xml отсутствует или недоступен',
        'slow_response' => 'Медленный ответ сервера',
        'heavy_page' => 'Тяжелая страница',
        'https_missing' => 'HTTPS не включен',
        'mixed_content' => 'Обнаружен mixed content',
        'duplicate_titles' => 'Обнаружены дубли title между страницами',
        'missing_canonical' => 'У части страниц нет canonical',
        'hreflang_conflicts' => 'Обнаружены конфликты hreflang',
        'sitemap_non_200_urls' => 'В sitemap есть URL с ответом не-200',
        'missing_mobile_viewport' => 'Отсутствует адаптивный viewport meta-тег',
        'soft_404_detected' => 'Обнаружен soft 404 на странице с кодом 200',
        'render_blocking_resources' => 'Слишком много CSS/JS, блокирующих рендер',
        'pagination_signals_incomplete' => 'Сигналы пагинации rel prev/next неполные',
        'empty_anchor_links' => 'Обнаружены пустые тексты ссылок',
        'images_missing_alt' => 'Найдены изображения без атрибута alt',
        'thin_content_detected' => 'Обнаружен тонкий контент',
        'low_text_to_html_ratio' => 'Низкое соотношение текста к HTML',
        'low_internal_linking' => 'Недостаточная внутренняя перелинковка',
        'html_structure_issues' => 'Обнаружены проблемы структуры HTML',
        'hreflang_missing_reciprocal' => 'Отсутствуют обратные hreflang-ссылки',
        'hreflang_missing_x_default' => 'Отсутствует x-default для переводных страниц',
        'crawl_budget_no_bot_hits' => 'Не найдено хитов ботов в логах за период',
        'crawl_budget_bot_5xx' => 'Боты получают 5xx ответы',
    ] : [
        'title_length_out_of_range' => 'Title length is outside recommended range',
        'description_length_out_of_range' => 'Description length is outside recommended range',
        'invalid_h1_count' => 'H1 count should be exactly 1',
        'not_indexable' => 'Page is marked as non-indexable',
        'robots_missing' => 'robots.txt is missing or inaccessible',
        'sitemap_missing' => 'sitemap.xml is missing or inaccessible',
        'slow_response' => 'Server response is slow',
        'heavy_page' => 'Page size is heavy',
        'https_missing' => 'HTTPS is not enforced',
        'mixed_content' => 'Mixed content detected',
        'duplicate_titles' => 'Duplicate titles detected across crawled pages',
        'missing_canonical' => 'Some crawled pages have no canonical',
        'hreflang_conflicts' => 'Hreflang conflicts detected',
        'sitemap_non_200_urls' => 'Sitemap contains URLs returning non-200 status',
        'missing_mobile_viewport' => 'Missing responsive viewport meta tag',
        'soft_404_detected' => 'Soft 404 detected on a 200 page',
        'render_blocking_resources' => 'Too many render-blocking CSS/JS resources',
        'pagination_signals_incomplete' => 'Pagination rel prev/next signals are incomplete',
        'empty_anchor_links' => 'Empty anchor texts detected',
        'images_missing_alt' => 'Images without alt attributes found',
        'thin_content_detected' => 'Thin content detected',
        'low_text_to_html_ratio' => 'Low text-to-HTML ratio',
        'low_internal_linking' => 'Low internal linking detected',
        'html_structure_issues' => 'HTML structure issues detected',
        'hreflang_missing_reciprocal' => 'Hreflang reciprocal links are missing',
        'hreflang_missing_x_default' => 'Missing x-default hreflang on translated pages',
        'crawl_budget_no_bot_hits' => 'No bot hits detected in logs for the period',
        'crawl_budget_bot_5xx' => 'Bots encountered 5xx errors',
    ];

    $profileLabels = $isRu
        ? ['generic' => 'Универсальный', 'media-platform' => 'Медиа-платформа', 'content-site' => 'Контентный сайт', 'storefront' => 'Витрина / каталог']
        : ['generic' => 'Generic', 'media-platform' => 'Media Platform', 'content-site' => 'Content Site', 'storefront' => 'Storefront'];

    $crawl = $report['crawl'] ?? [];
    $duplicates = $crawl['duplicates'] ?? [];
    $canonical = $crawl['canonicalAudit'] ?? [];
    $hreflang = $crawl['hreflangAudit'] ?? [];
    $sitemapAudit = $report['sitemapAudit'] ?? [];
@endphp

<main class="container">
    <section class="card">
        <header class="header">
            <h1>{{ $tr['title'] }}</h1>
            <p>{{ $tr['generatedAt'] }} {{ $generatedAt }}</p>
            <div class="meta">
                <span class="chip">{{ $tr['target'] }}: {{ $report['target']['finalUrl'] ?? '-' }}</span>
                <span class="chip">{{ $tr['checkedAt'] }}: {{ $report['checkedAt'] ?? '-' }}</span>
            </div>
        </header>
    </section>

    <section class="card">
        <div class="body">
            <div class="grid">
                <article class="metric"><div class="label">{{ $tr['score'] }}</div><div class="value">{{ $report['score']['value'] ?? 0 }}</div></article>
                <article class="metric"><div class="label">{{ $tr['httpStatus'] }}</div><div class="value">{{ $report['status']['httpCode'] ?? 0 }}</div></article>
                <article class="metric"><div class="label">{{ $tr['ttfb'] }}</div><div class="value">{{ $report['performance']['ttfbMsApprox'] ?? 0 }} ms</div></article>
                <article class="metric"><div class="label">{{ $tr['crawledPages'] }}</div><div class="value">{{ $crawl['scanned'] ?? 0 }}/{{ $crawl['limit'] ?? 0 }}</div></article>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <p><strong>{{ $isRu ? 'Профиль скоринга' : 'Scoring profile' }}:</strong> {{ $profileLabels[(string) ($report['profile']['key'] ?? $report['score']['profile'] ?? 'generic')] ?? ($report['score']['profile'] ?? '-') }}</p>
            @if(($report['score']['signals'] ?? []) !== [])
                @php
                    $signalList = array_map(
                        static fn (string $signal): string => $signalLabels[$signal] ?? $signal,
                        array_values(array_filter((array) $report['score']['signals'], static fn ($value): bool => is_string($value) && $value !== ''))
                    );
                @endphp
                <p><strong>{{ $tr['detectedSignals'] }}:</strong> {{ implode(', ', $signalList) }}</p>
            @endif
        </div>
    </section>

    <section class="card"><div class="body"><h2>{{ $tr['metaTags'] }}</h2>
        <p>Title: {{ $report['meta']['titleLength'] ?? 0 }}, Description: {{ $report['meta']['descriptionLength'] ?? 0 }}</p>
        <p>Canonical: {{ $report['meta']['canonical'] ?? '-' }}</p>
        <p>Meta robots: {{ $report['meta']['robots'] ?? '-' }}</p>
    </div></section>
    <section class="card"><div class="body"><h2>{{ $tr['indexabilityTitle'] }}</h2>
        <p>{{ $tr['indexable'] }}: {{ ($report['indexability']['indexable'] ?? false) ? $tr['yes'] : $tr['no'] }}</p>
        <p>X-Robots-Tag: {{ $report['indexability']['xRobotsTag'] ?? '-' }}</p>
        <p>{{ $isRu ? 'Причина' : 'Reason' }}: {{ $report['indexability']['reason'] ?? '-' }}</p>
    </div></section>
    <section class="card"><div class="body"><h2>{{ $tr['headingsTitle'] }}</h2>
        <p>H1: {{ $report['headings']['h1'] ?? 0 }}, H2: {{ $report['headings']['h2'] ?? 0 }}, H3: {{ $report['headings']['h3'] ?? 0 }}</p>
    </div></section>
    <section class="card"><div class="body"><h2>{{ $tr['linksTitle'] }}</h2>
        <p>{{ $isRu ? 'Внутренние' : 'Internal' }}: {{ $report['links']['internal'] ?? 0 }}, {{ $isRu ? 'Внешние' : 'External' }}: {{ $report['links']['external'] ?? 0 }}, Nofollow: {{ $report['links']['nofollow'] ?? 0 }}</p>
    </div></section>
    <section class="card"><div class="body"><h2>{{ $tr['performanceTitle'] }}</h2>
        <p>{{ $tr['ttfb'] }}: {{ $report['performance']['ttfbMsApprox'] ?? 0 }} ms</p>
        <p>{{ $isRu ? 'Вес страницы' : 'Page size' }}: {{ $report['performance']['pageSizeKb'] ?? 0 }} KB</p>
        <p>{{ $isRu ? 'Количество ресурсов' : 'Resources' }}: {{ $report['performance']['resourceCount'] ?? 0 }}</p>
        <p>{{ $isRu ? 'Ресурсы, блокирующие рендер' : 'Render-blocking resources' }}: {{ $report['performance']['renderBlocking']['total'] ?? 0 }}</p>
    </div></section>
    <section class="card"><div class="body"><h2>{{ $tr['crawlFilesTitle'] }}</h2>
        <p>robots.txt: {{ ($report['robots']['available'] ?? false) ? $tr['yes'] : $tr['no'] }} ({{ $report['robots']['status'] ?? 0 }})</p>
        <p>sitemap.xml: {{ ($report['sitemap']['available'] ?? false) ? $tr['yes'] : $tr['no'] }} ({{ $report['sitemap']['status'] ?? 0 }})</p>
        <p>{{ $isRu ? 'URL в sitemap' : 'Sitemap entries' }}: {{ $report['sitemap']['entries'] ?? 0 }}</p>
    </div></section>
    <section class="card"><div class="body"><h2>{{ $tr['securityTitle'] }}</h2>
        <p>HTTPS: {{ ($report['security']['https'] ?? false) ? $tr['yes'] : $tr['no'] }}</p>
        <p>Mixed content: {{ ($report['security']['mixedContent'] ?? false) ? $tr['yes'] : $tr['no'] }}</p>
        <p>CSP: {{ ($report['security']['hasCsp'] ?? false) ? $tr['yes'] : $tr['no'] }}</p>
        <p>HSTS: {{ ($report['security']['hasHsts'] ?? false) ? $tr['yes'] : $tr['no'] }}</p>
    </div></section>
    <section class="card"><div class="body"><h2>{{ $tr['technicalFlags'] }}</h2>
        <p>{{ $isRu ? 'Мобильная адаптация' : 'Mobile friendly' }}: {{ ($report['mobileFriendly']['isResponsive'] ?? false) ? $tr['yes'] : $tr['no'] }}</p>
        <p>{{ $isRu ? 'Найдена пагинация' : 'Pagination detected' }}: {{ ($report['pagination']['isPaginated'] ?? false) ? $tr['yes'] : $tr['no'] }}</p>
        <p>Soft 404: {{ ($report['soft404']['detected'] ?? false) ? $tr['yes'] : $tr['no'] }}</p>
    </div></section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['crawlSummary'] }}</h2>
            @if(($crawl['pages'] ?? []) === [])
                <p class="muted">{{ $tr['none'] }}</p>
            @else
                <table>
                    <thead><tr><th>{{ $tr['url'] }}</th><th>{{ $tr['status'] }}</th><th>{{ $tr['titleCol'] }}</th><th>{{ $tr['h1'] }}</th><th>{{ $tr['indexable'] }}</th></tr></thead>
                    <tbody>
                    @foreach(($crawl['pages'] ?? []) as $page)
                        <tr>
                            <td>{{ $page['url'] ?? '-' }}</td>
                            <td>{{ $page['status'] ?? '-' }}</td>
                            <td>{{ $page['title'] ?? '-' }}</td>
                            <td>{{ $page['h1Count'] ?? '-' }}</td>
                            <td>{{ ($page['indexable'] ?? false) ? $tr['yes'] : $tr['no'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </section>

    <section class="card"><div class="body"><h2>{{ $tr['duplicates'] }}</h2><p>Titles: {{ count($duplicates['titles'] ?? []) }}, Descriptions: {{ count($duplicates['descriptions'] ?? []) }}, H1: {{ count($duplicates['h1'] ?? []) }}</p></div></section>
    <section class="card"><div class="body"><h2>{{ $tr['canonical'] }}</h2><p>{{ $tr['missing'] }}: {{ count($canonical['missing'] ?? []) }}, {{ $tr['crossDomain'] }}: {{ count($canonical['crossDomain'] ?? []) }}, {{ $tr['invalid'] }}: {{ count($canonical['invalid'] ?? []) }}</p></div></section>
    <section class="card"><div class="body"><h2>{{ $tr['hreflang'] }}</h2><p>{{ $tr['pagesWithHreflang'] }}: {{ $hreflang['pagesWithHreflang'] ?? 0 }}, {{ $tr['missingSelfReference'] }}: {{ count($hreflang['pagesWithoutSelfReference'] ?? []) }}, {{ $tr['duplicateLangTags'] }}: {{ count($hreflang['duplicateLangTags'] ?? []) }}</p></div></section>
    <section class="card"><div class="body"><h2>{{ $tr['sitemap'] }}</h2><p>{{ $tr['sampled'] }}: {{ $sitemapAudit['sampled'] ?? 0 }}, {{ $tr['non200'] }}: {{ count($sitemapAudit['non200'] ?? []) }}</p></div></section>
    <section class="card"><div class="body"><h2>{{ $tr['contentQuality'] }}</h2>
        <p>{{ $isRu ? 'Количество слов' : 'Word count' }}: {{ $report['quality']['content']['wordCount'] ?? 0 }}</p>
        <p>{{ $isRu ? 'Соотношение text/HTML' : 'Text/HTML ratio' }}: {{ $report['quality']['content']['textToHtmlRatio'] ?? 0 }}%</p>
        <p>{{ $isRu ? 'Тонкий контент' : 'Thin content' }}: {{ ($report['quality']['content']['thinContent'] ?? false) ? $tr['yes'] : $tr['no'] }}</p>
    </div></section>
    <section class="card"><div class="body"><h2>{{ $tr['accessibility'] }}</h2>
        <p>{{ $isRu ? 'Изображения без alt' : 'Images without alt' }}: {{ $report['quality']['accessibility']['imagesWithoutAlt'] ?? 0 }}/{{ $report['quality']['accessibility']['imagesTotal'] ?? 0 }}</p>
        <p>{{ $isRu ? 'Нарушена иерархия заголовков' : 'Heading order broken' }}: {{ ($report['quality']['accessibility']['headingOrderBroken'] ?? false) ? $tr['yes'] : $tr['no'] }}</p>
        <p>{{ $isRu ? 'HTML проблемы' : 'HTML issues' }}: {{ $report['quality']['htmlValidation']['issueCount'] ?? 0 }}</p>
    </div></section>
    <section class="card"><div class="body"><h2>{{ $tr['linkGraph'] }}</h2>
        <p>{{ $isRu ? 'Внутренние исходящие' : 'Internal outlinks' }}: {{ $report['quality']['linkGraph']['internalOutlinks'] ?? 0 }}</p>
        <p>{{ $isRu ? 'Внешние исходящие' : 'External outlinks' }}: {{ $report['quality']['linkGraph']['externalOutlinks'] ?? 0 }}</p>
        <p>{{ $isRu ? 'Риск orphan-страницы' : 'Orphan risk' }}: {{ ($report['quality']['linkGraph']['orphanRisk'] ?? false) ? $tr['yes'] : $tr['no'] }}</p>
    </div></section>
    <section class="card"><div class="body"><h2>{{ $tr['international'] }}</h2>
        <p>{{ $tr['pagesWithHreflang'] }}: {{ $report['international']['pagesWithHreflang'] ?? 0 }}</p>
        <p>{{ $isRu ? 'Без x-default' : 'Missing x-default' }}: {{ count($report['international']['missingXDefault'] ?? []) }}</p>
        <p>{{ $isRu ? 'Без обратной hreflang-ссылки' : 'Missing reciprocal hreflang' }}: {{ count($report['international']['missingReciprocal'] ?? []) }}</p>
        <p>{{ $isRu ? 'Языковые кластеры' : 'Language clusters' }}: {{ count($report['international']['clusters'] ?? []) }}</p>
    </div></section>
    <section class="card"><div class="body"><h2>{{ $tr['crawlBudget'] }}</h2>
        <p>{{ $isRu ? 'Источник данных' : 'Data source' }}: {{ $report['crawlBudget']['source'] ?? '-' }}</p>
        <p>{{ $isRu ? 'Хитов ботов (7д)' : 'Bot hits (7d)' }}: {{ $report['crawlBudget']['botHits'] ?? 0 }}</p>
        <p>2xx/3xx/4xx/5xx: {{ $report['crawlBudget']['statusBuckets']['2xx'] ?? 0 }}/{{ $report['crawlBudget']['statusBuckets']['3xx'] ?? 0 }}/{{ $report['crawlBudget']['statusBuckets']['4xx'] ?? 0 }}/{{ $report['crawlBudget']['statusBuckets']['5xx'] ?? 0 }}</p>
    </div></section>

    <section class="card">
        <div class="body">
            <h2>{{ $tr['recommendations'] }}</h2>
            @if(($report['recommendations'] ?? []) === [])
                <p class="muted">{{ $tr['none'] }}</p>
            @else
                <ul>
                    @foreach(($report['recommendations'] ?? []) as $item)
                        @php
                            $priorityKey = (string) ($item['priority'] ?? 'low');
                            $recommendationKey = (string) ($item['key'] ?? '');
                        @endphp
                        <li>[{{ $priorityLabels[$priorityKey] ?? strtoupper($priorityKey) }}] {{ $recommendationLabels[$recommendationKey] ?? $recommendationKey }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>
</main>
</body>
</html>
