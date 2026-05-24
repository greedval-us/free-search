@php
    $snapshot = $snapshot ?? [];
    $topModules = $topModules ?? [];
    $dailyActivity = $dailyActivity ?? [];
    $period = (int) ($period ?? 7);
    $allowedPeriods = $allowedPeriods ?? [7, 30, 90];

    $usersTotal = (int) ($snapshot['users_total'] ?? 0);
    $usersPremiumActive = (int) ($snapshot['users_premium_active'] ?? 0);
    $usersBlocked = (int) ($snapshot['users_blocked'] ?? 0);
    $requests24h = (int) ($snapshot['requests_24h'] ?? 0);
    $requests7d = (int) ($snapshot['requests_7d'] ?? 0);
    $errors4xx24h = (int) ($snapshot['errors_4xx_24h'] ?? 0);
    $errors5xx24h = (int) ($snapshot['errors_5xx_24h'] ?? 0);
    $queueReady = (int) ($snapshot['queue_jobs_ready'] ?? 0);
    $queueInProgress = (int) ($snapshot['queue_jobs_in_progress'] ?? 0);
    $failed24h = (int) ($snapshot['failed_jobs_24h'] ?? 0);

    $premiumShare = $usersTotal > 0 ? round(($usersPremiumActive / $usersTotal) * 100, 1) : 0.0;
    $blockedShare = $usersTotal > 0 ? round(($usersBlocked / $usersTotal) * 100, 1) : 0.0;
    $errorShare = $requests24h > 0 ? round((($errors4xx24h + $errors5xx24h) / $requests24h) * 100, 1) : 0.0;

    $maxDailyRequests = max(1, (int) collect($dailyActivity)->max('requests_count'));
    $maxActiveUsers = max(1, (int) collect($dailyActivity)->max('active_users_count'));
    $maxModuleRequests = max(1, (int) collect($topModules)->max('requests_count'));

    $chartWidth = 640;
    $chartHeight = 220;
    $chartPaddingX = 16;
    $chartPaddingY = 18;
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

        $requestPoints[] = round($x, 2) . ',' . round($requestY, 2);
        $activeUserPoints[] = round($x, 2) . ',' . round($activeUsersY, 2);
        $requestDots[] = ['x' => $x, 'y' => $requestY, 'label' => (string) ($item['date'] ?? ''), 'value' => $requestCount];
        $activeUserDots[] = ['x' => $x, 'y' => $activeUsersY, 'label' => (string) ($item['date'] ?? ''), 'value' => $activeUsersCount];
    }
@endphp

<div class="space-y-3">
    <div class="box p-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h4 class="text-sm font-semibold">{{ __('admin_dashboard.visual.control_focus') }}</h4>
                <p class="text-xs text-secondary">{{ __('admin_dashboard.visual.control_subtitle') }}</p>
            </div>

            <div class="inline-flex rounded-lg border overflow-hidden">
                @foreach ($allowedPeriods as $candidate)
                    @php
                        $isActive = ((int) $candidate) === $period;
                    @endphp
                    <a
                        href="{{ request()->fullUrlWithQuery(['period' => (int) $candidate]) }}"
                        class="{{ $isActive ? 'btn btn-primary' : 'btn' }} rounded-none text-xs px-3 py-1.5"
                    >
                        {{ __('admin_dashboard.visual.period_days', ['days' => (int) $candidate]) }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-3 2xl:grid-cols-6">
        <div class="box p-3">
            <p class="text-xs text-secondary">{{ __('admin_dashboard.visual.premium_share') }}</p>
            <p class="mt-1 text-xl font-semibold">{{ $premiumShare }}%</p>
            <div class="mt-2 h-2 rounded overflow-hidden" style="background: rgba(148,163,184,0.25);">
                <div class="h-full" style="background-color: #22c55e; width: {{ min(100, max(0, $premiumShare)) }}%;"></div>
            </div>
        </div>

        <div class="box p-3">
            <p class="text-xs text-secondary">{{ __('admin_dashboard.visual.blocked_share') }}</p>
            <p class="mt-1 text-xl font-semibold">{{ $blockedShare }}%</p>
            <div class="mt-2 h-2 rounded overflow-hidden" style="background: rgba(148,163,184,0.25);">
                <div class="h-full" style="background-color: #ef4444; width: {{ min(100, max(0, $blockedShare)) }}%;"></div>
            </div>
        </div>

        <div class="box p-3">
            <p class="text-xs text-secondary">{{ __('admin_dashboard.visual.error_share_24h') }}</p>
            <p class="mt-1 text-xl font-semibold">{{ $errorShare }}%</p>
            <div class="mt-2 h-2 rounded overflow-hidden" style="background: rgba(148,163,184,0.25);">
                <div class="h-full" style="background-color: #f59e0b; width: {{ min(100, max(0, $errorShare)) }}%;"></div>
            </div>
        </div>

        <div class="box p-3">
            <p class="text-xs text-secondary">{{ __('admin_dashboard.visual.requests_growth_signal') }}</p>
            <p class="mt-1 text-xl font-semibold">
                {{ $requests7d >= ($requests24h * 2) ? __('admin_dashboard.visual.signal_up') : __('admin_dashboard.visual.signal_stable') }}
            </p>
            <p class="mt-2 text-xs text-secondary">
                24h: {{ number_format($requests24h, 0, '.', ' ') }} | 7d: {{ number_format($requests7d, 0, '.', ' ') }}
            </p>
        </div>

        <div class="box p-3">
            <p class="text-xs text-secondary">{{ __('admin_dashboard.visual.queue_status') }}</p>
            <p class="mt-1 text-xl font-semibold">{{ number_format($queueReady, 0, '.', ' ') }}</p>
            <p class="mt-2 text-xs text-secondary">
                {{ __('admin_dashboard.visual.ready_now') }}: {{ number_format($queueReady, 0, '.', ' ') }}
                <br>
                {{ __('admin_dashboard.visual.in_progress') }}: {{ number_format($queueInProgress, 0, '.', ' ') }}
            </p>
        </div>

        <div class="box p-3">
            <p class="text-xs text-secondary">{{ __('admin_dashboard.visual.failed_jobs') }}</p>
            <p class="mt-1 text-xl font-semibold">{{ number_format($failed24h, 0, '.', ' ') }}</p>
            <p class="mt-2 text-xs text-secondary">{{ __('admin_dashboard.visual.last_24h') }}</p>
        </div>
    </div>

    <div class="grid gap-3 xl:grid-cols-2">
        <div class="box p-3">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <h4 class="text-sm font-semibold">{{ __('admin_dashboard.visual.requests_by_day', ['days' => $period]) }}</h4>
                    <p class="text-xs text-secondary">{{ __('admin_dashboard.visual.daily_trend') }}</p>
                </div>
                <div class="text-xs text-secondary">
                    <span class="inline-flex items-center gap-1">
                        <span class="inline-block w-2 h-2 rounded-full bg-blue-500"></span>
                        {{ __('admin_dashboard.table.requests') }}
                    </span>
                    <span class="ml-3 inline-flex items-center gap-1">
                        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                        {{ __('admin_dashboard.table.active_users') }}
                    </span>
                </div>
            </div>

            <div class="mt-3">
                <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" class="w-full h-48">
                    <line x1="{{ $chartPaddingX }}" y1="{{ $chartHeight - $chartPaddingY }}" x2="{{ $chartWidth - $chartPaddingX }}" y2="{{ $chartHeight - $chartPaddingY }}" stroke="#94a3b8" stroke-width="1" stroke-dasharray="4 3"></line>
                    <polyline fill="none" stroke="#3b82f6" stroke-width="2.5" points="{{ implode(' ', $requestPoints) }}"></polyline>
                    <polyline fill="none" stroke="#10b981" stroke-width="2.5" points="{{ implode(' ', $activeUserPoints) }}"></polyline>

                    @foreach ($requestDots as $dot)
                        <circle cx="{{ $dot['x'] }}" cy="{{ $dot['y'] }}" r="3.2" fill="#3b82f6">
                            <title>{{ $dot['label'] }}: {{ __('admin_dashboard.table.requests') }} {{ number_format((int) $dot['value'], 0, '.', ' ') }}</title>
                        </circle>
                    @endforeach

                    @foreach ($activeUserDots as $dot)
                        <circle cx="{{ $dot['x'] }}" cy="{{ $dot['y'] }}" r="3.2" fill="#10b981">
                            <title>{{ $dot['label'] }}: {{ __('admin_dashboard.table.active_users') }} {{ number_format((int) $dot['value'], 0, '.', ' ') }}</title>
                        </circle>
                    @endforeach
                </svg>
            </div>
        </div>

        <div class="box p-3">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <h4 class="text-sm font-semibold">{{ __('admin_dashboard.visual.top_modules', ['days' => $period]) }}</h4>
                    <p class="text-xs text-secondary">{{ __('admin_dashboard.visual.by_total_requests') }}</p>
                </div>
                <span class="text-xs text-secondary">{{ __('admin_dashboard.visual.max') }}: {{ number_format($maxModuleRequests, 0, '.', ' ') }}</span>
            </div>

            <div class="mt-3 space-y-2">
                @forelse ($topModules as $module)
                    @php
                        $moduleName = (string) ($module['module_label'] ?? 'unknown');
                        $moduleRequests = (int) ($module['requests_count'] ?? 0);
                        $moduleBar = max(3, (int) round(($moduleRequests / $maxModuleRequests) * 100));
                    @endphp
                    <div class="space-y-1" title="{{ $moduleName }}: {{ $moduleRequests }}">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-medium">{{ $moduleName }}</span>
                            <span class="text-secondary">{{ number_format($moduleRequests, 0, '.', ' ') }}</span>
                        </div>
                        <div class="h-2 rounded overflow-hidden" style="background: rgba(148,163,184,0.25);">
                            <div class="h-full" style="background-color: #6366f1; width: {{ $moduleBar }}%;"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-secondary">{{ __('admin_dashboard.visual.no_module_usage') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
