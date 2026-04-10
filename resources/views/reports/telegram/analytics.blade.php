<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Telegram Analytics Report</title>
    <style>
        :root {
            color-scheme: light;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            font-family: "DejaVu Sans", sans-serif;
            color: #0f172a;
            background: #f8fafc;
            line-height: 1.45;
        }

        .container {
            max-width: 1120px;
            margin: 0 auto;
        }

        .card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            margin-bottom: 16px;
            overflow: hidden;
        }

        .header {
            padding: 20px 22px;
            background: linear-gradient(135deg, #0f172a, #1e3a8a 55%, #0891b2);
            color: #ffffff;
        }

        .header h1 {
            margin: 0 0 6px;
            font-size: 22px;
        }

        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px;
        }

        .chip {
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            padding: 4px 10px;
            font-size: 11px;
            white-space: nowrap;
        }

        .body {
            padding: 16px 18px;
        }

        h2 {
            font-size: 14px;
            margin: 0 0 12px;
            color: #0f172a;
        }

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
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
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

        .muted {
            color: #64748b;
        }
    </style>
</head>
<body>
    @php
        $reportLocale = ($locale ?? 'en') === 'ru' ? 'ru' : 'en';

        $dictionary = [
            'en' => [
                'title' => 'Telegram Analytics Report',
                'generatedAt' => 'Generated at',
                'channel' => 'Channel',
                'range' => 'Range',
                'profile' => 'Scoring profile',
                'keyword' => 'Keyword',
                'formula' => 'Formula',
                'groupInfo' => 'Group Info',
                'groupType' => 'Type',
                'groupParticipants' => 'Participants',
                'groupOnline' => 'Online',
                'groupCreated' => 'Created',
                'groupLinkedChatId' => 'Linked Chat ID',
                'groupStatsAvailable' => 'Statistics available',
                'groupVerified' => 'Verified',
                'groupRestricted' => 'Restricted',
                'groupScam' => 'Scam',
                'groupTypeChannel' => 'Channel',
                'groupTypeGroup' => 'Group',
                'groupTypeForum' => 'Forum',
                'groupTypeGigagroup' => 'Gigagroup',
                'groupTypeChat' => 'Chat',
                'noGroupInfo' => 'Group info not available',
                'totals' => 'Totals',
                'messages' => 'Messages',
                'views' => 'Views',
                'forwards' => 'Forwards',
                'replies' => 'Replies',
                'reactions' => 'Reactions',
                'mediaPosts' => 'Media Posts',
                'avgViewsPerPost' => 'Avg Views/Post',
                'avgInteractionsPerPost' => 'Avg Interactions/Post',
                'timeline' => 'Timeline',
                'funnel' => 'Engagement Funnel',
                'audience' => 'Audience',
                'bucket' => 'Bucket',
                'interactions' => 'Interactions',
                'conversionPrev' => 'Conversion vs Previous Stage, %',
                'conversionStart' => 'Conversion vs First Stage, %',
                'activeAuthors' => 'Active Authors',
                'singleMessageAuthors' => 'Single-Message Authors',
                'returningAuthors' => 'Returning Authors',
                'topAuthorShare' => 'Top Author Share, %',
                'top5AuthorsShare' => 'Top 5 Authors Share, %',
                'concentrationIndex' => 'Concentration Index',
                'mostActiveHours' => 'Most Active Hours',
                'antiFraud' => 'Anti-Fraud',
                'riskScore' => 'Risk Score',
                'riskLevel' => 'Risk Level',
                'fraudTriggers' => 'Triggers',
                'fraudSuspiciousPosts' => 'Suspicious Posts',
                'fraudNoTriggers' => 'No strong fraud signals detected',
                'fraudReason' => 'Reasons',
                'fraudRiskLow' => 'Low',
                'fraudRiskMedium' => 'Medium',
                'fraudRiskHigh' => 'High',
                'topPosts' => 'Top Posts',
                'opinionLeaders' => 'Opinion Leaders',
                'opinionLeadersByDay' => 'Opinion Leaders By Day',
                'date' => 'Date',
                'author' => 'Author',
                'message' => 'Message',
                'score' => 'Score',
                'gifts' => 'Gifts',
                'noTimeline' => 'No timeline data',
                'noFunnel' => 'No funnel data',
                'noAudience' => 'No audience data',
                'noTopPosts' => 'No top posts for this range',
                'noOpinionLeaders' => 'Not enough authors to build opinion leaders',
                'noOpinionLeadersByDay' => 'No daily activity data for opinion leaders',
            ],
            'ru' => [
                'title' => 'Отчет по аналитике Telegram',
                'generatedAt' => 'Сформирован',
                'channel' => 'Канал',
                'range' => 'Период',
                'profile' => 'Профиль оценки',
                'keyword' => 'Ключевое слово',
                'formula' => 'Формула',
                'groupInfo' => 'Информация о группе',
                'groupType' => 'Тип',
                'groupParticipants' => 'Участники',
                'groupOnline' => 'Онлайн',
                'groupCreated' => 'Создан',
                'groupLinkedChatId' => 'ID связанного чата',
                'groupStatsAvailable' => 'Статистика доступна',
                'groupVerified' => 'Верифицирован',
                'groupRestricted' => 'Ограничен',
                'groupScam' => 'Скам',
                'groupTypeChannel' => 'Канал',
                'groupTypeGroup' => 'Группа',
                'groupTypeForum' => 'Форум',
                'groupTypeGigagroup' => 'Гигагруппа',
                'groupTypeChat' => 'Чат',
                'noGroupInfo' => 'Информация о группе недоступна',
                'totals' => 'Итоги',
                'messages' => 'Сообщения',
                'views' => 'Просмотры',
                'forwards' => 'Репосты',
                'replies' => 'Ответы',
                'reactions' => 'Реакции',
                'mediaPosts' => 'Посты с медиа',
                'avgViewsPerPost' => 'Сред. просмотров/пост',
                'avgInteractionsPerPost' => 'Сред. взаимодействий/пост',
                'timeline' => 'Динамика',
                'funnel' => 'Воронка вовлечения',
                'audience' => 'Аудитория',
                'bucket' => 'Срез',
                'interactions' => 'Взаимодействия',
                'conversionPrev' => 'Конверсия к предыдущему этапу, %',
                'conversionStart' => 'Конверсия к первому этапу, %',
                'activeAuthors' => 'Активные авторы',
                'singleMessageAuthors' => 'Авторы с 1 сообщением',
                'returningAuthors' => 'Повторно активные',
                'topAuthorShare' => 'Доля топ-автора, %',
                'top5AuthorsShare' => 'Доля топ-5 авторов, %',
                'concentrationIndex' => 'Индекс концентрации',
                'mostActiveHours' => 'Самые активные часы',
                'antiFraud' => 'Anti-Fraud',
                'riskScore' => 'Риск-скор',
                'riskLevel' => 'Уровень риска',
                'fraudTriggers' => 'Триггеры',
                'fraudSuspiciousPosts' => 'Подозрительные посты',
                'fraudNoTriggers' => 'Сильные сигналы фрода не обнаружены',
                'fraudReason' => 'Причины',
                'fraudRiskLow' => 'Низкий',
                'fraudRiskMedium' => 'Средний',
                'fraudRiskHigh' => 'Высокий',
                'topPosts' => 'Топ постов',
                'opinionLeaders' => 'Лидеры мнений',
                'opinionLeadersByDay' => 'Лидеры мнений по дням',
                'date' => 'Дата',
                'author' => 'Автор',
                'message' => 'Сообщение',
                'score' => 'Оценка',
                'gifts' => 'Подарки',
                'noTimeline' => 'Нет данных по динамике',
                'noFunnel' => 'Нет данных по воронке',
                'noAudience' => 'Нет данных по аудитории',
                'noTopPosts' => 'Нет топ-постов за выбранный период',
                'noOpinionLeaders' => 'Недостаточно авторов для оценки лидеров мнений',
                'noOpinionLeadersByDay' => 'Нет дневных данных активности по лидерам мнений',
            ],
        ];

        $tr = $dictionary[$reportLocale];
    @endphp
    <main class="container">
        <section class="card">
            <header class="header">
                <h1>{{ $tr['title'] }}</h1>
                <p>{{ $tr['generatedAt'] }} {{ $generatedAt }}</p>
                <div class="meta">
                    <span class="chip">{{ $tr['channel'] }}: {{ $report['range']['chatUsername'] ?? '-' }}</span>
                    <span class="chip">{{ $tr['range'] }}: {{ $report['range']['label'] ?? '-' }}</span>
                    <span class="chip">{{ $tr['profile'] }}: {{ $report['score']['priority'] ?? 'balanced' }}</span>
                    @if(!empty($report['range']['keyword']))
                        <span class="chip">{{ $tr['keyword'] }}: {{ $report['range']['keyword'] }}</span>
                    @endif
                    <span class="chip">
                        {{ $tr['formula'] }}:
                        {{ $report['score']['weights']['views'] ?? 0 }}*V +
                        {{ $report['score']['weights']['forwards'] ?? 0 }}*F +
                        {{ $report['score']['weights']['replies'] ?? 0 }}*R +
                        {{ $report['score']['weights']['reactions'] ?? 0 }}*Re +
                        {{ $report['score']['weights']['gifts'] ?? 0 }}*G
                    </span>
                </div>
            </header>
        </section>

        <section class="card">
            <div class="body">
                <h2>{{ $tr['groupInfo'] }}</h2>
                @php
                    $groupInfo = $report['groupInfo'] ?? null;
                    $groupTypeKey = $groupInfo['type'] ?? 'chat';
                    $groupTypeLabel = match ($groupTypeKey) {
                        'channel' => $tr['groupTypeChannel'],
                        'group' => $tr['groupTypeGroup'],
                        'forum' => $tr['groupTypeForum'],
                        'gigagroup' => $tr['groupTypeGigagroup'],
                        default => $tr['groupTypeChat'],
                    };
                @endphp

                @if(is_array($groupInfo))
                    <div class="grid">
                        <article class="metric">
                            <div class="label">{{ $tr['channel'] }}</div>
                            <div class="value" style="font-size: 18px;">{{ $groupInfo['title'] ?? ($report['range']['chatUsername'] ?? '-') }}</div>
                        </article>
                        <article class="metric">
                            <div class="label">Username</div>
                            <div class="value" style="font-size: 18px;">
                                @if(!empty($groupInfo['username']))
                                    {{ '@' . $groupInfo['username'] }}
                                @else
                                    -
                                @endif
                            </div>
                        </article>
                        <article class="metric">
                            <div class="label">{{ $tr['groupType'] }}</div>
                            <div class="value" style="font-size: 18px;">{{ $groupTypeLabel }}</div>
                        </article>
                        <article class="metric">
                            <div class="label">{{ $tr['groupParticipants'] }}</div>
                            <div class="value">{{ isset($groupInfo['participantsCount']) ? $groupInfo['participantsCount'] : '-' }}</div>
                        </article>
                        <article class="metric">
                            <div class="label">{{ $tr['groupOnline'] }}</div>
                            <div class="value">{{ isset($groupInfo['onlineCount']) ? $groupInfo['onlineCount'] : '-' }}</div>
                        </article>
                        <article class="metric">
                            <div class="label">{{ $tr['groupCreated'] }}</div>
                            <div class="value" style="font-size: 18px;">
                                @if(!empty($groupInfo['createdAt']))
                                    {{ \Carbon\Carbon::createFromTimestamp((int) $groupInfo['createdAt'], config('app.timezone'))->format('d.m.Y H:i') }}
                                @else
                                    -
                                @endif
                            </div>
                        </article>
                    </div>

                    @if(!empty($groupInfo['description']))
                        <p class="muted" style="margin-top: 10px;">{{ $groupInfo['description'] }}</p>
                    @endif

                    <div class="meta" style="margin-top: 10px;">
                        @if(!empty($groupInfo['verified']))
                            <span class="chip">{{ $tr['groupVerified'] }}</span>
                        @endif
                        @if(!empty($groupInfo['restricted']))
                            <span class="chip">{{ $tr['groupRestricted'] }}</span>
                        @endif
                        @if(!empty($groupInfo['scam']))
                            <span class="chip">{{ $tr['groupScam'] }}</span>
                        @endif
                        @if(!empty($groupInfo['canViewStats']))
                            <span class="chip">{{ $tr['groupStatsAvailable'] }}</span>
                        @endif
                        @if(!empty($groupInfo['linkedChatId']))
                            <span class="chip">{{ $tr['groupLinkedChatId'] }}: {{ $groupInfo['linkedChatId'] }}</span>
                        @endif
                    </div>
                @else
                    <p class="muted">{{ $tr['noGroupInfo'] }}</p>
                @endif
            </div>
        </section>

        <section class="card">
            <div class="body">
                <h2>{{ $tr['totals'] }}</h2>
                <div class="grid">
                    <article class="metric">
                        <div class="label">{{ $tr['messages'] }}</div>
                        <div class="value">{{ $report['summary']['totals']['messages'] ?? 0 }}</div>
                    </article>
                    <article class="metric">
                        <div class="label">{{ $tr['views'] }}</div>
                        <div class="value">{{ $report['summary']['totals']['views'] ?? 0 }}</div>
                    </article>
                    <article class="metric">
                        <div class="label">{{ $tr['forwards'] }}</div>
                        <div class="value">{{ $report['summary']['totals']['forwards'] ?? 0 }}</div>
                    </article>
                    <article class="metric">
                        <div class="label">{{ $tr['replies'] }}</div>
                        <div class="value">{{ $report['summary']['totals']['replies'] ?? 0 }}</div>
                    </article>
                    <article class="metric">
                        <div class="label">{{ $tr['reactions'] }}</div>
                        <div class="value">{{ $report['summary']['totals']['reactions'] ?? 0 }}</div>
                    </article>
                    <article class="metric">
                        <div class="label">{{ $tr['mediaPosts'] }}</div>
                        <div class="value">{{ $report['summary']['totals']['mediaPosts'] ?? 0 }}</div>
                    </article>
                    <article class="metric">
                        <div class="label">{{ $tr['avgViewsPerPost'] }}</div>
                        <div class="value">{{ $report['summary']['totals']['avgViewsPerPost'] ?? 0 }}</div>
                    </article>
                    <article class="metric">
                        <div class="label">{{ $tr['avgInteractionsPerPost'] }}</div>
                        <div class="value">{{ $report['summary']['totals']['avgInteractionsPerPost'] ?? 0 }}</div>
                    </article>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="body">
                <h2>{{ $tr['funnel'] }}</h2>
                <table>
                    <thead>
                        <tr>
                            <th>{{ $tr['bucket'] }}</th>
                            <th>{{ $tr['messages'] }}</th>
                            <th>{{ $tr['conversionPrev'] }}</th>
                            <th>{{ $tr['conversionStart'] }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $funnelLabels = [
                                'messages' => $tr['messages'],
                                'views' => $tr['views'],
                                'interactions' => $tr['interactions'],
                                'reactions' => $tr['reactions'],
                            ];
                        @endphp
                        @forelse(($report['summary']['funnel']['stages'] ?? []) as $index => $stage)
                            @php
                                $currentValue = (int) ($stage['value'] ?? 0);
                                $previousValue = $index === 0
                                    ? $currentValue
                                    : (int) (($report['summary']['funnel']['stages'][$index - 1]['value'] ?? 0));
                                $startValue = (int) (($report['summary']['funnel']['stages'][0]['value'] ?? 0));
                            @endphp
                            <tr>
                                <td>{{ $funnelLabels[$stage['key'] ?? ''] ?? ($stage['key'] ?? '-') }}</td>
                                <td>{{ $stage['value'] ?? 0 }}</td>
                                <td>{{ $stage['conversionFromPrevious'] ?? 0 }}% ({{ $currentValue }} / {{ $previousValue }})</td>
                                <td>{{ $stage['conversionFromStart'] ?? 0 }}% ({{ $currentValue }} / {{ $startValue }})</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="muted">{{ $tr['noFunnel'] }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <div class="body">
                <h2>{{ $tr['audience'] }}</h2>
                @php
                    $audience = $report['summary']['audience'] ?? null;
                @endphp
                @if(is_array($audience))
                    <div class="grid">
                        <article class="metric">
                            <div class="label">{{ $tr['activeAuthors'] }}</div>
                            <div class="value">{{ $audience['activeAuthors'] ?? 0 }}</div>
                        </article>
                        <article class="metric">
                            <div class="label">{{ $tr['singleMessageAuthors'] }}</div>
                            <div class="value">{{ $audience['singleMessageAuthors'] ?? 0 }}</div>
                        </article>
                        <article class="metric">
                            <div class="label">{{ $tr['returningAuthors'] }}</div>
                            <div class="value">{{ $audience['returningAuthors'] ?? 0 }}</div>
                        </article>
                        <article class="metric">
                            <div class="label">{{ $tr['topAuthorShare'] }}</div>
                            <div class="value">{{ $audience['topAuthorShare'] ?? 0 }}%</div>
                        </article>
                        <article class="metric">
                            <div class="label">{{ $tr['top5AuthorsShare'] }}</div>
                            <div class="value">{{ $audience['top5AuthorsShare'] ?? 0 }}%</div>
                        </article>
                        <article class="metric">
                            <div class="label">{{ $tr['concentrationIndex'] }}</div>
                            <div class="value">{{ $audience['concentrationIndex'] ?? 0 }}</div>
                        </article>
                    </div>

                    <h2 style="margin-top: 14px;">{{ $tr['mostActiveHours'] }}</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>{{ $tr['bucket'] }}</th>
                                <th>{{ $tr['messages'] }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($audience['mostActiveHours'] ?? []) as $hour)
                                <tr>
                                    <td>{{ $hour['label'] ?? '-' }}</td>
                                    <td>{{ $hour['messages'] ?? 0 }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="muted">{{ $tr['noAudience'] }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                @else
                    <p class="muted">{{ $tr['noAudience'] }}</p>
                @endif
            </div>
        </section>

        <section class="card">
            <div class="body">
                <h2>{{ $tr['antiFraud'] }}</h2>
                @php
                    $fraud = $report['summary']['fraudSignals'] ?? null;
                    $fraudTriggerLabels = [
                        'zero_view_interactions' => $reportLocale === 'ru' ? 'Взаимодействия без просмотров' : 'Interactions without views',
                        'author_concentration' => $reportLocale === 'ru' ? 'Высокая концентрация на одном авторе' : 'High author concentration',
                        'time_burst' => $reportLocale === 'ru' ? 'Всплеск активности в одном временном слоте' : 'Time burst concentration',
                        'reaction_ratio_cluster' => $reportLocale === 'ru' ? 'Кластер с высоким отношением реакций' : 'Cluster with high reaction ratio',
                        'suspicious_posts_cluster' => $reportLocale === 'ru' ? 'Кластер подозрительных постов' : 'Cluster of suspicious posts',
                    ];
                    $fraudReasonLabels = [
                        'interactions_without_views' => $reportLocale === 'ru' ? 'Взаимодействия без просмотров' : 'Interactions without views',
                        'high_reaction_ratio' => $reportLocale === 'ru' ? 'Аномально высокий уровень реакций' : 'Abnormally high reaction ratio',
                        'high_forward_ratio' => $reportLocale === 'ru' ? 'Аномально высокий уровень репостов' : 'Abnormally high forward ratio',
                        'gifts_with_low_views' => $reportLocale === 'ru' ? 'Подарки при низких просмотрах' : 'Gifts with low views',
                        'high_interactions_low_views' => $reportLocale === 'ru' ? 'Высокие взаимодействия при низких просмотрах' : 'High interactions with low views',
                    ];
                    $riskLevelLabel = match (($fraud['riskLevel'] ?? 'low')) {
                        'high' => $tr['fraudRiskHigh'],
                        'medium' => $tr['fraudRiskMedium'],
                        default => $tr['fraudRiskLow'],
                    };
                @endphp

                @if(is_array($fraud))
                    <div class="grid">
                        <article class="metric">
                            <div class="label">{{ $tr['riskScore'] }}</div>
                            <div class="value">{{ $fraud['riskScore'] ?? 0 }}</div>
                        </article>
                        <article class="metric">
                            <div class="label">{{ $tr['riskLevel'] }}</div>
                            <div class="value">{{ $riskLevelLabel }}</div>
                        </article>
                        <article class="metric">
                            <div class="label">{{ $tr['fraudSuspiciousPosts'] }}</div>
                            <div class="value">{{ $fraud['suspiciousPostsCount'] ?? 0 }}</div>
                        </article>
                    </div>

                    <h2 style="margin-top: 14px;">{{ $tr['fraudTriggers'] }}</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>{{ $tr['bucket'] }}</th>
                                <th>{{ $tr['score'] }}</th>
                                <th>{{ $tr['messages'] }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($fraud['triggers'] ?? []) as $trigger)
                                <tr>
                                    <td>{{ $fraudTriggerLabels[$trigger['key'] ?? ''] ?? ($trigger['key'] ?? '-') }}</td>
                                    <td>+{{ $trigger['score'] ?? 0 }}</td>
                                    <td>{{ $trigger['value'] ?? 0 }} / {{ $trigger['threshold'] ?? 0 }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="muted">{{ $tr['fraudNoTriggers'] }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <h2 style="margin-top: 14px;">{{ $tr['fraudSuspiciousPosts'] }}</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ $tr['date'] }}</th>
                                <th>{{ $tr['message'] }}</th>
                                <th>{{ $tr['riskScore'] }}</th>
                                <th>{{ $tr['fraudReason'] }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($fraud['suspiciousPosts'] ?? []) as $index => $post)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        @if(isset($post['date']))
                                            {{ \Carbon\Carbon::createFromTimestamp((int) $post['date'], config('app.timezone'))->format('d.m.Y H:i') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $post['message'] ?? '-' }}</td>
                                    <td>{{ $post['riskScore'] ?? 0 }}</td>
                                    <td>
                                        @php
                                            $reasons = is_array($post['reasons'] ?? null) ? $post['reasons'] : [];
                                            $reasonLabels = array_map(
                                                static fn ($key) => $fraudReasonLabels[$key] ?? (string) $key,
                                                $reasons
                                            );
                                        @endphp
                                        {{ implode(' · ', $reasonLabels) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="muted">{{ $tr['fraudNoTriggers'] }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                @else
                    <p class="muted">{{ $tr['fraudNoTriggers'] }}</p>
                @endif
            </div>
        </section>

        <section class="card">
            <div class="body">
                <h2>{{ $tr['timeline'] }}</h2>
                <table>
                    <thead>
                        <tr>
                            <th>{{ $tr['bucket'] }}</th>
                            <th>{{ $tr['messages'] }}</th>
                            <th>{{ $tr['views'] }}</th>
                            <th>{{ $tr['forwards'] }}</th>
                            <th>{{ $tr['replies'] }}</th>
                            <th>{{ $tr['reactions'] }}</th>
                            <th>{{ $tr['interactions'] }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($report['summary']['timeline'] ?? []) as $item)
                            <tr>
                                <td>{{ $item['label'] ?? '-' }}</td>
                                <td>{{ $item['messages'] ?? 0 }}</td>
                                <td>{{ $item['views'] ?? 0 }}</td>
                                <td>{{ $item['forwards'] ?? 0 }}</td>
                                <td>{{ $item['replies'] ?? 0 }}</td>
                                <td>{{ $item['reactions'] ?? 0 }}</td>
                                <td>{{ $item['interactions'] ?? 0 }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="muted">{{ $tr['noTimeline'] }}</td>
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
                            <th>{{ $tr['date'] }}</th>
                            <th>{{ $tr['message'] }}</th>
                            <th>{{ $tr['views'] }}</th>
                            <th>{{ $tr['forwards'] }}</th>
                            <th>{{ $tr['replies'] }}</th>
                            <th>{{ $tr['reactions'] }}</th>
                            <th>{{ $tr['score'] }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($report['summary']['topPosts'] ?? []) as $index => $post)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    @if(isset($post['date']))
                                        {{ \Carbon\Carbon::createFromTimestamp((int) $post['date'], config('app.timezone'))->format('d.m.Y H:i') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $post['message'] ?? '-' }}</td>
                                <td>{{ $post['views'] ?? 0 }}</td>
                                <td>{{ $post['forwards'] ?? 0 }}</td>
                                <td>{{ $post['replies'] ?? 0 }}</td>
                                <td>{{ $post['reactions'] ?? 0 }}</td>
                                <td>{{ $post['score'] ?? 0 }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="muted">{{ $tr['noTopPosts'] }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <div class="body">
                <h2>{{ $tr['opinionLeaders'] }}</h2>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ $tr['author'] }}</th>
                            <th>{{ $tr['messages'] }}</th>
                            <th>{{ $tr['forwards'] }}</th>
                            <th>{{ $tr['replies'] }}</th>
                            <th>{{ $tr['reactions'] }}</th>
                            <th>{{ $tr['gifts'] }}</th>
                            <th>{{ $tr['interactions'] }}</th>
                            <th>{{ $tr['score'] }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(count($report['summary']['opinionLeaders'] ?? []) > 1)
                            @foreach(($report['summary']['opinionLeaders'] ?? []) as $index => $leader)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $leader['authorLabel'] ?? ('ID ' . ($leader['authorId'] ?? '-')) }}</td>
                                    <td>{{ $leader['messages'] ?? 0 }}</td>
                                    <td>{{ $leader['forwards'] ?? 0 }}</td>
                                    <td>{{ $leader['replies'] ?? 0 }}</td>
                                    <td>{{ $leader['reactions'] ?? 0 }}</td>
                                    <td>{{ $leader['gifts'] ?? 0 }}</td>
                                    <td>{{ $leader['interactions'] ?? 0 }}</td>
                                    <td>{{ $leader['score'] ?? 0 }}</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="9" class="muted">{{ $tr['noOpinionLeaders'] }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <div class="body">
                <h2>{{ $tr['opinionLeadersByDay'] }}</h2>
                <table>
                    <thead>
                        <tr>
                            <th>{{ $tr['date'] }}</th>
                            <th>{{ $tr['author'] }}</th>
                            <th>{{ $tr['messages'] }}</th>
                            <th>{{ $tr['forwards'] }}</th>
                            <th>{{ $tr['replies'] }}</th>
                            <th>{{ $tr['reactions'] }}</th>
                            <th>{{ $tr['gifts'] }}</th>
                            <th>{{ $tr['interactions'] }}</th>
                            <th>{{ $tr['score'] }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($report['summary']['opinionLeadersDaily'] ?? []) as $row)
                            <tr>
                                <td>{{ $row['dayLabel'] ?? '-' }}</td>
                                <td>{{ $row['authorLabel'] ?? ('ID ' . ($row['authorId'] ?? '-')) }}</td>
                                <td>{{ $row['messages'] ?? 0 }}</td>
                                <td>{{ $row['forwards'] ?? 0 }}</td>
                                <td>{{ $row['replies'] ?? 0 }}</td>
                                <td>{{ $row['reactions'] ?? 0 }}</td>
                                <td>{{ $row['gifts'] ?? 0 }}</td>
                                <td>{{ $row['interactions'] ?? 0 }}</td>
                                <td>{{ $row['score'] ?? 0 }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="muted">{{ $tr['noOpinionLeadersByDay'] }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
