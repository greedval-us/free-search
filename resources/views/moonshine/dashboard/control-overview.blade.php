@php
    $snapshot = $snapshot ?? [];
    $topModules = $topModules ?? [];
    $dailyActivity = $dailyActivity ?? [];
    $period = (int) ($period ?? 7);

    $usersTotal = (int) ($snapshot['users_total'] ?? 0);
    $usersPaidActive = (int) ($snapshot['users_paid_active'] ?? 0);
    $requests24h = (int) ($snapshot['requests_24h'] ?? 0);
    $errors4xx24h = (int) ($snapshot['errors_4xx_24h'] ?? 0);
    $errors5xx24h = (int) ($snapshot['errors_5xx_24h'] ?? 0);
    $queueReady = (int) ($snapshot['queue_jobs_ready'] ?? 0);
    $queueInProgress = (int) ($snapshot['queue_jobs_in_progress'] ?? 0);
    $parserRunsActive = (int) ($snapshot['parser_runs_active'] ?? 0);
    $parserRunsCompleted = (int) ($snapshot['parser_runs_completed_24h'] ?? 0);
    $parserRunsFailed = (int) ($snapshot['parser_runs_failed_24h'] ?? 0);

    $paidShare = $usersTotal > 0 ? round(($usersPaidActive / $usersTotal) * 100, 1) : 0.0;
    $errorShare = $requests24h > 0 ? round((($errors4xx24h + $errors5xx24h) / $requests24h) * 100, 1) : 0.0;
    $maxDailyRequests = max(1, (int) collect($dailyActivity)->max('requests_count'));
    $maxActiveUsers = max(1, (int) collect($dailyActivity)->max('active_users_count'));
    $maxModuleRequests = max(1, (int) collect($topModules)->max('requests_count'));

    $chartWidth = 720;
    $chartHeight = 230;
    $chartPaddingX = 18;
    $chartPaddingY = 20;
    $pointsCount = max(1, count($dailyActivity));
    $stepX = $pointsCount > 1 ? (($chartWidth - ($chartPaddingX * 2)) / ($pointsCount - 1)) : 0.0;
    $requestPoints = [];
    $activeUserPoints = [];
    $requestDots = [];
    $activeUserDots = [];

    foreach ($dailyActivity as $index => $item) {
        $x = $chartPaddingX + ($stepX * $index);
        $requestCount = (int) ($item['requests_count'] ?? 0);
        $activeUsersCount = (int) ($item['active_users_count'] ?? 0);
        $requestY = $chartHeight - $chartPaddingY - (($requestCount / $maxDailyRequests) * ($chartHeight - ($chartPaddingY * 2)));
        $activeUsersY = $chartHeight - $chartPaddingY - (($activeUsersCount / $maxActiveUsers) * ($chartHeight - ($chartPaddingY * 2)));

        $requestPoints[] = round($x, 2).','.round($requestY, 2);
        $activeUserPoints[] = round($x, 2).','.round($activeUsersY, 2);
        $requestDots[] = ['x' => $x, 'y' => $requestY, 'label' => (string) ($item['date'] ?? ''), 'value' => $requestCount];
        $activeUserDots[] = ['x' => $x, 'y' => $activeUsersY, 'label' => (string) ($item['date'] ?? ''), 'value' => $activeUsersCount];
    }
@endphp

<section class="ur-admin-analytics">
    <div class="ur-admin-section-heading">
        <div>
            <span>{{ __('admin_dashboard.visual.control_focus') }}</span>
            <h3>{{ __('admin_dashboard.sections.visual_analytics') }}</h3>
        </div>
        <p>{{ __('admin_dashboard.visual.control_subtitle') }}</p>
    </div>

    <div class="ur-admin-signal-grid">
        <article class="ur-admin-signal">
            <span>{{ __('admin_dashboard.visual.paid_share') }}</span>
            <strong>{{ $paidShare }}%</strong>
            <div class="ur-admin-progress"><i style="width: {{ min(100, max(0, $paidShare)) }}%"></i></div>
        </article>

        <article class="ur-admin-signal">
            <span>{{ __('admin_dashboard.visual.error_share_24h') }}</span>
            <strong>{{ $errorShare }}%</strong>
            <div class="ur-admin-progress ur-admin-progress--warning"><i style="width: {{ min(100, max(0, $errorShare)) }}%"></i></div>
        </article>

        <article class="ur-admin-signal">
            <span>{{ __('admin_dashboard.visual.queue_status') }}</span>
            <strong>{{ number_format($queueReady, 0, '.', ' ') }}</strong>
            <small>{{ __('admin_dashboard.visual.in_progress') }}: {{ number_format($queueInProgress, 0, '.', ' ') }}</small>
        </article>

        <article class="ur-admin-signal">
            <span>{{ __('admin_dashboard.visual.parser_status') }}</span>
            <strong>{{ number_format($parserRunsActive, 0, '.', ' ') }}</strong>
            <small>
                {{ __('admin_dashboard.visual.completed_24h') }}: {{ number_format($parserRunsCompleted, 0, '.', ' ') }} &middot;
                {{ __('admin_dashboard.visual.failed_24h') }}: {{ number_format($parserRunsFailed, 0, '.', ' ') }}
            </small>
        </article>
    </div>

    <div class="ur-admin-chart-grid">
        <article class="ur-admin-chart-card">
            <header>
                <div>
                    <h4>{{ __('admin_dashboard.visual.requests_by_day', ['days' => $period]) }}</h4>
                    <p>{{ __('admin_dashboard.visual.daily_trend') }}</p>
                </div>
                <div class="ur-admin-legend">
                    <span><i class="is-requests"></i>{{ __('admin_dashboard.table.requests') }}</span>
                    <span><i class="is-users"></i>{{ __('admin_dashboard.table.active_users') }}</span>
                </div>
            </header>

            <div class="ur-admin-chart-scroll">
                <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" role="img" aria-label="{{ __('admin_dashboard.visual.daily_trend') }}">
                    <line x1="{{ $chartPaddingX }}" y1="{{ $chartHeight - $chartPaddingY }}" x2="{{ $chartWidth - $chartPaddingX }}" y2="{{ $chartHeight - $chartPaddingY }}" class="ur-admin-chart-axis"></line>
                    <polyline class="ur-admin-chart-line is-requests" points="{{ implode(' ', $requestPoints) }}"></polyline>
                    <polyline class="ur-admin-chart-line is-users" points="{{ implode(' ', $activeUserPoints) }}"></polyline>

                    @foreach ($requestDots as $dot)
                        <circle cx="{{ $dot['x'] }}" cy="{{ $dot['y'] }}" r="3.5" class="ur-admin-chart-dot is-requests">
                            <title>{{ $dot['label'] }}: {{ __('admin_dashboard.table.requests') }} {{ number_format((int) $dot['value'], 0, '.', ' ') }}</title>
                        </circle>
                    @endforeach

                    @foreach ($activeUserDots as $dot)
                        <circle cx="{{ $dot['x'] }}" cy="{{ $dot['y'] }}" r="3.5" class="ur-admin-chart-dot is-users">
                            <title>{{ $dot['label'] }}: {{ __('admin_dashboard.table.active_users') }} {{ number_format((int) $dot['value'], 0, '.', ' ') }}</title>
                        </circle>
                    @endforeach
                </svg>
            </div>
        </article>

        <article class="ur-admin-chart-card">
            <header>
                <div>
                    <h4>{{ __('admin_dashboard.visual.top_modules', ['days' => $period]) }}</h4>
                    <p>{{ __('admin_dashboard.visual.by_total_requests') }}</p>
                </div>
                <span class="ur-admin-max">{{ __('admin_dashboard.visual.max') }}: {{ number_format($maxModuleRequests, 0, '.', ' ') }}</span>
            </header>

            <div class="ur-admin-module-list">
                @forelse ($topModules as $module)
                    @php
                        $moduleName = (string) ($module['module_label'] ?? 'unknown');
                        $moduleRequests = (int) ($module['requests_count'] ?? 0);
                        $moduleUsers = (int) ($module['users_count'] ?? 0);
                        $moduleBar = max(3, (int) round(($moduleRequests / $maxModuleRequests) * 100));
                    @endphp
                    <div class="ur-admin-module" title="{{ $moduleName }}: {{ $moduleRequests }}">
                        <div>
                            <strong>{{ $moduleName }}</strong>
                            <small>{{ trans_choice('admin_dashboard.visual.users_count', $moduleUsers, ['count' => $moduleUsers]) }}</small>
                            <span>{{ number_format($moduleRequests, 0, '.', ' ') }}</span>
                        </div>
                        <div class="ur-admin-module__track"><i style="width: {{ $moduleBar }}%"></i></div>
                    </div>
                @empty
                    <p class="ur-admin-empty">{{ __('admin_dashboard.visual.no_module_usage') }}</p>
                @endforelse
            </div>
        </article>
    </div>
</section>
