<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bluesky Analytics Report</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; font-family: "DejaVu Sans", sans-serif; color: #0f172a; background: #f8fafc; line-height: 1.45; }
        .container { max-width: 1120px; margin: 0 auto; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; margin-bottom: 16px; overflow: hidden; }
        .header { padding: 20px 22px; background: linear-gradient(135deg, #082f49, #0f766e 55%, #38bdf8); color: #fff; }
        .header h1 { margin: 0 0 6px; font-size: 22px; }
        .meta { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
        .chip { border-radius: 999px; border: 1px solid rgba(255,255,255,.25); padding: 4px 10px; font-size: 11px; white-space: nowrap; }
        .body { padding: 16px 18px; }
        h2 { font-size: 14px; margin: 0 0 12px; color: #0f172a; }
        .grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; }
        .metric { border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; background: #f8fafc; }
        .metric .label { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: .05em; }
        .metric .value { margin-top: 6px; font-size: 20px; font-weight: 700; color: #0f172a; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; border-bottom: 1px solid #e2e8f0; padding: 8px 6px; font-size: 12px; vertical-align: top; }
        th { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: .05em; }
        .tag-row { display: flex; flex-wrap: wrap; gap: 8px; }
        .tag-chip { border: 1px solid #cbd5e1; border-radius: 999px; padding: 4px 10px; font-size: 11px; background: #f8fafc; }
        .muted { color: #64748b; }
        @media (max-width: 960px) { .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 640px) { .grid { grid-template-columns: repeat(1, minmax(0, 1fr)); } }
    </style>
</head>
<body>
@php
    $summary = (array) ($report['summary'] ?? []);
    $meta = (array) ($report['meta'] ?? []);
    $topPosts = (array) ($report['topPosts'] ?? []);
    $timeline = (array) ($report['timeline'] ?? []);
    $topDomains = (array) ($report['topDomains'] ?? []);
    $topTags = (array) ($report['topTags'] ?? []);
    $topAuthors = (array) ($report['topAuthors'] ?? []);
@endphp
<div class="container">
    <section class="card">
        <header class="header">
            <h1>Bluesky Analytics Report</h1>
            <div class="meta">
                <span class="chip">Mode: {{ $meta['mode'] ?? '-' }}</span>
                <span class="chip">Target: {{ $meta['resolvedTarget'] ?? ($meta['target'] ?? '-') }}</span>
                <span class="chip">Sampled posts: {{ $meta['sampledPosts'] ?? 0 }}</span>
                <span class="chip">Pages: {{ $meta['pagesLoaded'] ?? 0 }}/{{ $meta['pagesRequested'] ?? 0 }}</span>
                <span class="chip">Generated: {{ now()->format('Y-m-d H:i:s') }}</span>
            </div>
        </header>
    </section>

    <section class="card">
        <div class="body">
            <h2>Summary</h2>
            <div class="grid">
                <div class="metric"><div class="label">Posts</div><div class="value">{{ number_format((int) ($summary['postsCount'] ?? 0)) }}</div></div>
                <div class="metric"><div class="label">Authors</div><div class="value">{{ number_format((int) ($summary['uniqueAuthorsCount'] ?? 0)) }}</div></div>
                <div class="metric"><div class="label">Languages</div><div class="value">{{ number_format((int) ($summary['uniqueLanguagesCount'] ?? 0)) }}</div></div>
                <div class="metric"><div class="label">Posts with media</div><div class="value">{{ number_format((int) ($summary['postsWithMediaCount'] ?? 0)) }}</div></div>
                <div class="metric"><div class="label">Posts with links</div><div class="value">{{ number_format((int) ($summary['postsWithLinksCount'] ?? 0)) }}</div></div>
                <div class="metric"><div class="label">Replies</div><div class="value">{{ number_format((int) ($summary['totalReplies'] ?? 0)) }}</div></div>
                <div class="metric"><div class="label">Reposts</div><div class="value">{{ number_format((int) ($summary['totalReposts'] ?? 0)) }}</div></div>
                <div class="metric"><div class="label">Likes</div><div class="value">{{ number_format((int) ($summary['totalLikes'] ?? 0)) }}</div></div>
                <div class="metric"><div class="label">Quotes</div><div class="value">{{ number_format((int) ($summary['totalQuotes'] ?? 0)) }}</div></div>
                <div class="metric"><div class="label">Reply posts</div><div class="value">{{ number_format((int) ($summary['replyPostsCount'] ?? 0)) }}</div></div>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>Timeline</h2>
            <table>
                <thead>
                    <tr>
                        <th>Day</th><th>Posts</th><th>Media</th><th>Links</th><th>Replies</th><th>Reposts</th><th>Likes</th><th>Quotes</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($timeline as $point)
                    <tr>
                        <td>{{ $point['day'] ?? '-' }}</td>
                        <td>{{ $point['posts'] ?? 0 }}</td>
                        <td>{{ $point['postsWithMedia'] ?? 0 }}</td>
                        <td>{{ $point['postsWithLinks'] ?? 0 }}</td>
                        <td>{{ $point['replies'] ?? 0 }}</td>
                        <td>{{ $point['reposts'] ?? 0 }}</td>
                        <td>{{ $point['likes'] ?? 0 }}</td>
                        <td>{{ $point['quotes'] ?? 0 }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">No timeline data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>Top Posts</h2>
            <table>
                <thead>
                    <tr>
                        <th>Author</th><th>Created at</th><th>Likes</th><th>Reposts</th><th>Replies</th><th>Quotes</th><th>Text</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($topPosts as $post)
                    <tr>
                        <td>{{ data_get($post, 'author.displayName') ?: data_get($post, 'author.handle', '-') }}</td>
                        <td>{{ $post['createdAt'] ?? '-' }}</td>
                        <td>{{ $post['likeCount'] ?? 0 }}</td>
                        <td>{{ $post['repostCount'] ?? 0 }}</td>
                        <td>{{ $post['replyCount'] ?? 0 }}</td>
                        <td>{{ $post['quoteCount'] ?? 0 }}</td>
                        <td>{{ $post['text'] ?? '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No top posts.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>Top Domains</h2>
            <div class="tag-row">
                @forelse($topDomains as $domain)
                    <span class="tag-chip">{{ $domain['domain'] ?? '-' }} | {{ $domain['count'] ?? 0 }}</span>
                @empty
                    <span class="muted">No domains.</span>
                @endforelse
            </div>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>Top Hashtags</h2>
            <div class="tag-row">
                @forelse($topTags as $tag)
                    <span class="tag-chip">#{{ $tag['tag'] ?? '-' }} | {{ $tag['count'] ?? 0 }}</span>
                @empty
                    <span class="muted">No hashtags.</span>
                @endforelse
            </div>
        </div>
    </section>

    <section class="card">
        <div class="body">
            <h2>Top Authors</h2>
            <table>
                <thead>
                    <tr><th>Author</th><th>Handle</th><th>Count</th></tr>
                </thead>
                <tbody>
                @forelse($topAuthors as $author)
                    <tr>
                        <td>{{ $author['displayName'] ?? '-' }}</td>
                        <td>{{ $author['handle'] ?? '-' }}</td>
                        <td>{{ $author['count'] ?? 0 }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted">No authors.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
</body>
</html>
