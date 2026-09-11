<section class="ur-admin-hero">
    <div class="ur-admin-hero__glow" aria-hidden="true"></div>
    <div class="ur-admin-hero__content">
        <div class="ur-admin-hero__copy">
            <div class="ur-admin-eyebrow">{{ __('admin_bot.navigation') }}</div>
            <h2>{{ __('admin_bot.overview') }}</h2>
            <p>{{ __('admin_bot.subtitle') }}</p>
        </div>
        <div class="ur-admin-hero__meta">
            <span class="ur-admin-health {{ $snapshot['configured'] ? '' : 'ur-admin-health--warning' }}">
                {{ __('admin_bot.'.($snapshot['configured'] ? 'configured' : 'not_configured')) }}
            </span>
            <span>{{ $snapshot['username'] === '' ? '-' : '@'.$snapshot['username'] }}</span>
            <span>{{ __('admin_dashboard.hero.updated_at', ['date' => $snapshot['generated_at']->format('d.m.Y H:i')]) }}</span>
        </div>
    </div>
    <p class="ur-bot-note">{{ __('admin_bot.configuration_hint') }}</p>
    <div class="ur-admin-toolbar">
        <span class="ur-admin-toolbar__label">{{ __('admin_dashboard.hero.period') }}</span>
        <nav class="ur-admin-periods" aria-label="{{ __('admin_dashboard.hero.period') }}">
            @foreach ($snapshot['periods'] as $period)
                <a
                    href="{{ request()->fullUrlWithQuery(['period' => $period]) }}"
                    class="ur-admin-period {{ $snapshot['period'] === $period ? 'is-active' : '' }}"
                    @if ($snapshot['period'] === $period) aria-current="page" @endif
                >{{ __('admin_dashboard.visual.period_days', ['days' => $period]) }}</a>
            @endforeach
        </nav>
    </div>
    <nav class="ur-admin-quick-links" aria-label="{{ __('admin_bot.navigation') }}">
        @foreach ($links as $link)
            <a href="{{ $link['url'] }}" class="ur-admin-quick-link">
                <strong>{{ $link['title'] }}</strong>
                <span aria-hidden="true">&rarr;</span>
            </a>
        @endforeach
    </nav>
</section>

<section class="ur-admin-analytics ur-bot-overview">
    <div class="ur-admin-signal-grid">
        @foreach (['linked_total' => $snapshot['links'], 'new_links' => $snapshot['new_links'], 'pending_links' => $snapshot['pending_links'], 'deliveries_total' => $snapshot['deliveries']] as $label => $count)
            <article class="ur-admin-signal">
                <span>{{ __('admin_bot.'.$label) }}</span>
                <strong>{{ number_format($count) }}</strong>
            </article>
        @endforeach
    </div>
    <p class="ur-bot-note">{{ __('admin_bot.retention_hint', ['days' => $snapshot['retention_days']]) }}</p>
    <div class="ur-admin-chart-grid">
        <article class="ur-admin-chart-card">
            <header><h3>{{ __('admin_bot.outcomes') }}</h3></header>
            <dl class="ur-bot-facts">
                @foreach ($snapshot['statuses'] as $row)
                    <div><dt>{{ $row['label'] }}</dt><dd>{{ number_format($row['count']) }}</dd></div>
                @endforeach
                <div>
                    <dt>{{ __('admin_bot.sent_share') }}</dt>
                    <dd>{{ $snapshot['sent_share'] === null ? '-' : $snapshot['sent_share'].'%' }}</dd>
                </div>
            </dl>
            <p class="ur-bot-note">{{ __('admin_bot.cohort_hint') }}</p>
        </article>
        <article class="ur-admin-chart-card">
            <header><h3>{{ __('admin_bot.preferences') }}</h3></header>
            <dl class="ur-bot-facts">
                @foreach ($snapshot['consents'] as $key => $count)
                    <div><dt>{{ __('admin_bot.'.$key.'_enabled') }}</dt><dd>{{ number_format($count) }}</dd></div>
                @endforeach
            </dl>
            <h3>{{ __('admin_bot.by_kind') }}</h3>
            <dl class="ur-bot-facts">
                @foreach ($snapshot['kinds'] as $row)
                    <div><dt>{{ $row['label'] }}</dt><dd>{{ number_format($row['count']) }}</dd></div>
                @endforeach
            </dl>
        </article>
    </div>
    <article class="ur-admin-chart-card">
        <header><h3>{{ __('admin_bot.daily') }}</h3></header>
        @if ($snapshot['deliveries'] === 0)
            <p class="ur-admin-empty">{{ __('admin_bot.empty') }}</p>
        @else
            <div class="ur-bot-table-scroll" role="region" aria-label="{{ __('admin_bot.daily') }}" tabindex="0">
                <table class="ur-bot-table">
                    <thead><tr>
                        <th scope="col">{{ __('admin_panel.fields.date') }}</th>
                        <th scope="col">{{ __('admin_bot.created') }}</th>
                        <th scope="col">{{ __('admin_bot.sent') }}</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($snapshot['daily'] as $row)
                            <tr>
                                <th scope="row">{{ $row['date'] }}</th>
                                <td>
                                    {{ $row['total'] }}
                                    <div class="ur-admin-progress" aria-hidden="true"><i style="width: {{ round($row['total'] * 100 / $snapshot['daily_max'], 2) }}%"></i></div>
                                </td>
                                <td>{{ $row['sent'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </article>
    @if ($diagnostics !== null)
        <article class="ur-admin-chart-card">
            <header><h3>{{ __('admin_bot.diagnostics') }}</h3></header>
            <p class="ur-bot-note">{{ __('admin_bot.queue_hint') }}</p>
            <dl class="ur-bot-facts">
                <div><dt>{{ __('admin_panel.fields.connection') }}</dt><dd>{{ $diagnostics['connection'] }}</dd></div>
                <div><dt>{{ __('admin_panel.fields.queue') }}</dt><dd>{{ $diagnostics['queue'] }}</dd></div>
                <div><dt>{{ __('admin_bot.driver') }}</dt><dd>{{ $diagnostics['driver'] }}</dd></div>
                <div><dt>{{ __('admin_bot.queue_state') }}</dt><dd>{{ __('admin_bot.queue_'.$diagnostics['queue_state']) }}</dd></div>
                <div><dt>{{ __('admin_bot.queue_size') }}</dt><dd>{{ $diagnostics['queue_size'] ?? '-' }}</dd></div>
                <div><dt>{{ __('admin_bot.pending') }}</dt><dd>{{ $diagnostics['pending'] }}</dd></div>
                <div><dt>{{ __('admin_bot.stale', ['minutes' => $diagnostics['warning_minutes']]) }}</dt><dd>{{ $diagnostics['stale'] }}</dd></div>
                <div><dt>{{ __('admin_bot.oldest_pending') }}</dt><dd>{{ $diagnostics['oldest_pending_at']?->format('d.m.Y H:i') ?? '-' }}</dd></div>
                <div><dt>{{ __('admin_bot.last_sent') }}</dt><dd>{{ $diagnostics['last_sent_at']?->format('d.m.Y H:i') ?? '-' }}</dd></div>
            </dl>
        </article>
    @endif
</section>
