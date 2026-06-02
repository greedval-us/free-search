<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mastodon Analytics Report</title>
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
            'title' => 'Mastodon Analytics Report',
            'generatedAt' => 'Generated at',
            'mode' => 'Mode',
            'target' => 'Target',
            'sample' => 'Sample',
            'resolvedTarget' => 'Resolved target',
            'posts' => 'Posts',
            'accounts' => 'Accounts',
            'instances' => 'Instances',
            'languages' => 'Languages',
            'mediaPosts' => 'Posts with media',
            'linkPosts' => 'Posts with links',
            'replyPosts' => 'Reply posts',
            'boostPosts' => 'Boost posts',
            'sensitivePosts' => 'Sensitive posts',
            'repliesTotal' => 'Replies total',
            'reblogsTotal' => 'Boosts total',
            'favouritesTotal' => 'Favorites total',
            'timeline' => 'Timeline',
            'day' => 'Day',
            'topPosts' => 'Top posts',
            'topDomains' => 'Top domains',
            'topTags' => 'Top tags',
            'topAccounts' => 'Top accounts',
            'topMentions' => 'Top mentions',
            'topLanguages' => 'Top languages',
            'count' => 'Count',
            'author' => 'Author',
            'createdAt' => 'Created at',
            'content' => 'Content',
            'none' => 'No data',
        ],
        'ru' => [
            'title' => 'Отчёт по аналитике Mastodon',
            'generatedAt' => 'Сформирован',
            'mode' => 'Режим',
            'target' => 'Цель',
            'sample' => 'Выборка',
            'resolvedTarget' => 'Разрешённая цель',
            'posts' => 'Посты',
            'accounts' => 'Аккаунты',
            'instances' => 'Инстансы',
            'languages' => 'Языки',
            'mediaPosts' => 'Посты с медиа',
            'linkPosts' => 'Посты со ссылками',
            'replyPosts' => 'Посты-ответы',
            'boostPosts' => 'Бусты',
            'sensitivePosts' => 'Чувствительные посты',
            'repliesTotal' => 'Ответов всего',
            'reblogsTotal' => 'Бустов всего',
            'favouritesTotal' => 'Избранного всего',
            'timeline' => 'Таймлайн',
            'day' => 'День',
            'topPosts' => 'Топ постов',
            'topDomains' => 'Топ доменов',
            'topTags' => 'Топ тегов',
            'topAccounts' => 'Топ аккаунтов',
            'topMentions' => 'Топ упоминаний',
            'topLanguages' => 'Топ языков',
            'count' => 'Количество',
            'author' => 'Автор',
            'createdAt' => 'Дата',
            'content' => 'Содержимое',
            'none' => 'Нет данных',
        ],
    ][$reportLocale];

    $profile = is_array($report['profile'] ?? null) ? $report['profile'] : null;
    $meta = is_array($report['meta'] ?? null) ? $report['meta'] : [];
    $summary = is_array($report['summary'] ?? null) ? $report['summary'] : [];
@endphp

<main class="container">
    <section class="card">
        <header class="header">
            <h1>{{ $tr['title'] }}</h1>
            <p>{{ $tr['generatedAt'] }} {{ $generatedAt }}</p>
            <div class="meta">
                <span class="chip">{{ $tr['mode'] }}: {{ $meta['mode'] ?? '-' }}</span>
                <span class="chip">{{ $tr['target'] }}: {{ $meta['target'] ?? '-' }}</span>
                <span class="chip">{{ $tr['resolvedTarget'] }}: {{ $meta['resolvedTarget'] ?? '-' }}</span>
            </div>
        </header>
    </section>

    @if(is_array($profile))
        <section class="card">
            <div class="body">
                <h2>{{ ($meta['mode'] ?? '') === 'account' ? 'Profile' : 'Hashtag' }}</h2>
                <table>
                    <tbody>
                    @foreach($profile as $key => $value)
                        @continue(is_array($value))
                        <tr>
                            <th>{{ (string) $key }}</th>
                            <td>{{ is_bool($value) ? ($value ? 'true' : 'false') : ($value !== '' ? $value : '-') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <section class="card">
        <div class="body">
            <h2>{{ $tr['sample'] }}</h2>
            <div class="grid">
                <article class="metric">
                    <div class="label">{{ $tr['posts'] }}</div>
                    <div class="value">{{ $summary['postsCount'] ?? 0 }}</div>
                </article>
                <article class="metric">
                    <div class="label">{{ $tr['accounts'] }}</div>
                    <div class="value">{{ $summary['uniqueAccountsCount'] ?? 0 }}</div>
                </article>
                <article class="metric">
                    <div class="label">{{ $tr['instances'] }}</div>
                    <div class="value">{{ $summary['uniqueInstancesCount'] ?? 0 }}</div>
                </article>
                <article class="metric">
                    <div class="label">{{ $tr['languages'] }}</div>
                    <div class="value">{{ $summary['uniqueLanguagesCount'] ?? 0 }}</div>
                </article>
                <article class="metric">
                    <div class="label">{{ $tr['mediaPosts'] }}</div>
                    <div class="value">{{ $summary['postsWithMediaCount'] ?? 0 }}</div>
                </article>
                <article class="metric">
                    <div class="label">{{ $tr['linkPosts'] }}</div>
                    <div class="value">{{ $summary['postsWithLinksCount'] ?? 0 }}</div>
                </article>
                <article class="metric">
                    <div class="label">{{ $tr['replyPosts'] }}</div>
                    <div class="value">{{ $summary['replyPostsCount'] ?? 0 }}</div>
                </article>
                <article class="metric">
                    <div class="label">{{ $tr['boostPosts'] }}</div>
                    <div class="value">{{ $summary['boostPostsCount'] ?? 0 }}</div>
                </article>
                <article class="metric">
                    <div class="label">{{ $tr['sensitivePosts'] }}</div>
                    <div class="value">{{ $summary['sensitivePostsCount'] ?? 0 }}</div>
                </article>
                <article class="metric">
                    <div class="label">{{ $tr['repliesTotal'] }}</div>
                    <div class="value">{{ $summary['totalReplies'] ?? 0 }}</div>
                </article>
                <article class="metric">
                    <div class="label">{{ $tr['reblogsTotal'] }}</div>
                    <div class="value">{{ $summary['totalReblogs'] ?? 0 }}</div>
                </article>
                <article class="metric">
                    <div class="label">{{ $tr['favouritesTotal'] }}</div>
                    <div class="value">{{ $summary['totalFavourites'] ?? 0 }}</div>
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
                    <th>{{ $tr['day'] }}</th>
                    <th>{{ $tr['posts'] }}</th>
                    <th>{{ $tr['mediaPosts'] }}</th>
                    <th>{{ $tr['linkPosts'] }}</th>
                    <th>{{ $tr['repliesTotal'] }}</th>
                    <th>{{ $tr['reblogsTotal'] }}</th>
                    <th>{{ $tr['favouritesTotal'] }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse(($report['timeline'] ?? []) as $row)
                    <tr>
                        <td>{{ $row['day'] ?? '-' }}</td>
                        <td>{{ $row['posts'] ?? 0 }}</td>
                        <td>{{ $row['postsWithMedia'] ?? 0 }}</td>
                        <td>{{ $row['postsWithLinks'] ?? 0 }}</td>
                        <td>{{ $row['replies'] ?? 0 }}</td>
                        <td>{{ $row['reblogs'] ?? 0 }}</td>
                        <td>{{ $row['favourites'] ?? 0 }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="muted">{{ $tr['none'] }}</td>
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
                    <th>{{ $tr['author'] }}</th>
                    <th>{{ $tr['createdAt'] }}</th>
                    <th>{{ $tr['content'] }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse(($report['topPosts'] ?? []) as $index => $post)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $post['account']['acct'] ?? '-' }}</td>
                        <td>{{ $post['createdAt'] ?? '-' }}</td>
                        <td>{{ $post['content'] ?? '-' }}</td>
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

    @foreach ([
        'topDomains' => 'topDomains',
        'topTags' => 'topTags',
        'topAccounts' => 'topAccounts',
        'topMentions' => 'topMentions',
        'topLanguages' => 'topLanguages',
    ] as $key => $labelKey)
        <section class="card">
            <div class="body">
                <h2>{{ $tr[$labelKey] }}</h2>
                @php $items = $report[$key] ?? []; @endphp
                @if (in_array($key, ['topDomains', 'topTags'], true))
                    <div class="tag-row">
                        @forelse($items as $item)
                            <span class="tag-chip">
                                {{ $item['domain'] ?? $item['tag'] ?? '-' }} ({{ $item['count'] ?? 0 }})
                            </span>
                        @empty
                            <span class="muted">{{ $tr['none'] }}</span>
                        @endforelse
                    </div>
                @else
                    <table>
                        <thead>
                        <tr>
                            <th>{{ $tr['target'] }}</th>
                            <th>{{ $tr['count'] }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>{{ $item['acct'] ?? $item['language'] ?? $item['displayName'] ?? $item['username'] ?? '-' }}</td>
                                <td>{{ $item['count'] ?? 0 }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="muted">{{ $tr['none'] }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                @endif
            </div>
        </section>
    @endforeach
</main>
</body>
</html>
