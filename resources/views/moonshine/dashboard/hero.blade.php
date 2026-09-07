@php
    $quickLinks = $quickLinks ?? [];
    $period = (int) ($period ?? 7);
    $allowedPeriods = $allowedPeriods ?? [7, 30, 90];
    $healthStatus = (string) ($healthStatus ?? 'healthy');
@endphp

<section class="ur-admin-hero">
    <div class="ur-admin-hero__glow" aria-hidden="true"></div>

    <div class="ur-admin-hero__content">
        <div class="ur-admin-hero__copy">
            <div class="ur-admin-eyebrow">
                <span class="ur-admin-status-dot ur-admin-status-dot--{{ $healthStatus }}"></span>
                {{ __('admin_dashboard.hero.workspace') }} &middot; {{ $roleLabel }}
            </div>
            <h2>{{ __('admin_dashboard.hero.title') }}</h2>
            <p>{{ __('admin_dashboard.hero.subtitle') }}</p>
        </div>

        <div class="ur-admin-hero__meta">
            <span class="ur-admin-health ur-admin-health--{{ $healthStatus }}">
                {{ __('admin_dashboard.health.'.$healthStatus) }}
            </span>
            <span>{{ __('admin_dashboard.hero.updated_at', ['date' => $generatedAt]) }}</span>
        </div>
    </div>

    <div class="ur-admin-toolbar">
        <span class="ur-admin-toolbar__label">{{ __('admin_dashboard.hero.period') }}</span>
        <div class="ur-admin-periods" aria-label="{{ __('admin_dashboard.hero.period') }}">
            @foreach ($allowedPeriods as $candidate)
                <a
                    href="{{ request()->fullUrlWithQuery(['period' => (int) $candidate]) }}"
                    class="ur-admin-period {{ ((int) $candidate) === $period ? 'is-active' : '' }}"
                    @if (((int) $candidate) === $period) aria-current="page" @endif
                >
                    {{ __('admin_dashboard.visual.period_days', ['days' => (int) $candidate]) }}
                </a>
            @endforeach
        </div>
    </div>

    @if ($quickLinks !== [])
        <nav class="ur-admin-quick-links" aria-label="{{ __('admin_dashboard.hero.quick_actions') }}">
            @foreach ($quickLinks as $link)
                <a href="{{ $link['url'] }}" class="ur-admin-quick-link">
                    <span>
                        <strong>{{ $link['title'] }}</strong>
                        <small>{{ $link['description'] }}</small>
                    </span>
                    <span class="ur-admin-quick-link__arrow" aria-hidden="true">&rarr;</span>
                </a>
            @endforeach
        </nav>
    @endif
</section>
