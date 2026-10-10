@if(isset($report['reportSchedule']) && is_array($report['reportSchedule']))
    @php
        $scheduled = $report['reportSchedule'];
        $scheduleLocale = ($locale ?? 'en') === 'ru' ? 'ru' : 'en';
        $scheduleType = in_array($scheduled['type'] ?? '', ['analytics', 'seo-audit'], true) ? $scheduled['type'] : 'analytics';
    @endphp
    <section class="card">
        <div class="body">
            <h2>{{ __('site_intel_reports.report.title', [], $scheduleLocale) }}</h2>
            <p>{{ __('site_intel_reports.report.type', [], $scheduleLocale) }}: {{ __('site_intel_reports.report.'.$scheduleType, [], $scheduleLocale) }}</p>
            <p>{{ __('site_intel_reports.report.scheduled_for', [], $scheduleLocale) }}: {{ $scheduled['scheduledFor'] ?? '-' }}</p>
            <p>{{ __('site_intel_reports.report.checked_at', [], $scheduleLocale) }}: {{ $scheduled['checkedAt'] ?? ($report['checkedAt'] ?? '-') }}</p>
            <p>{{ __('site_intel_reports.report.timezone', [], $scheduleLocale) }}: {{ $scheduled['timezone'] ?? '-' }}</p>
            <p class="muted">{{ __('site_intel_reports.report.methodology', [], $scheduleLocale) }}</p>
        </div>
    </section>
@endif
