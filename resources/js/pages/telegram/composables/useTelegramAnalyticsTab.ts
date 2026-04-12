import { computed, ref } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useTelegramAnalytics } from './useTelegramAnalytics';

type TrendSeriesKey = 'messages' | 'views' | 'interactions';

export const useTelegramAnalyticsTab = () => {
    const { t } = useI18n();

    const {
        PERIODS,
        PRIORITIES,
        form,
        loading,
        comparisonLoading,
        error,
        payload,
        previousPayload,
        periodLabel,
        dateLimits,
        trendMax,
        totalMessages,
        activePriority,
        applyPreset,
        setPriority,
        loadAnalytics,
        openReport,
        downloadReport,
    } = useTelegramAnalytics(t);

    const priorityLabel = (priority: string) => t(`telegram.analytics.priority.${priority}`);
    const analyticsPanelCollapsed = ref(false);
    const canLoadAnalytics = computed(() => form.chatUsername.trim().length > 0);
    const canUseReportActions = computed(() => !loading.value && !comparisonLoading.value && !!payload.value);
    const groupInfo = computed(() => payload.value?.groupInfo ?? null);
    const groupTypeLabel = (type: string): string => {
        const map: Record<string, string> = {
            channel: t('telegram.analytics.group.types.channel'),
            group: t('telegram.analytics.group.types.group'),
            forum: t('telegram.analytics.group.types.forum'),
            gigagroup: t('telegram.analytics.group.types.gigagroup'),
            chat: t('telegram.analytics.group.types.chat'),
        };

        return map[type] ?? type;
    };

    const mediaLabel = (key: string) => {
        const map: Record<string, string> = {
            photo: t('telegram.mediaTypes.photo'),
            video: t('telegram.mediaTypes.video'),
            document: t('telegram.mediaTypes.document'),
            audio: t('telegram.mediaTypes.audio'),
            geo: t('telegram.mediaTypes.geo'),
            poll: t('telegram.mediaTypes.poll'),
            contact: t('telegram.mediaTypes.contact'),
            link_preview: t('telegram.mediaTypes.link_preview'),
            other: t('telegram.mediaTypes.other'),
            none: t('telegram.mediaTypes.none'),
        };

        return map[key] ?? key;
    };

    const formatNumber = (value: number | null | undefined) => {
        const numeric = Number(value);

        return new Intl.NumberFormat().format(Number.isFinite(numeric) ? numeric : 0);
    };

    const formatDelta = (current: number, previous: number | null): string | null => {
        if (previous === null || previous === undefined) {
            return null;
        }

        if (previous === 0) {
            if (current === 0) {
                return '0%';
            }

            return 'n/a';
        }

        const delta = ((current - previous) / previous) * 100;
        const sign = delta > 0 ? '+' : '';

        return `${sign}${delta.toFixed(1)}%`;
    };

    const formatDate = (unix: number) => {
        if (!unix) {
            return '-';
        }

        return new Date(unix * 1000).toLocaleString();
    };

    const timeline = computed(() => payload.value?.summary.timeline ?? []);

    const chartWidth = 920;
    const chartHeight = 280;
    const padding = {
        top: 24,
        right: 20,
        bottom: 42,
        left: 20,
    };

    const chartInnerWidth = chartWidth - padding.left - padding.right;
    const chartInnerHeight = chartHeight - padding.top - padding.bottom;
    const hoveredIndex = ref<number | null>(null);

    const visibleSeries = ref<Record<TrendSeriesKey, boolean>>({
        messages: true,
        views: true,
        interactions: true,
    });

    const trendSeries = computed<Array<{
        key: TrendSeriesKey;
        label: string;
        color: string;
        values: number[];
    }>>(() => {
        const buckets = timeline.value;

        return [
            {
                key: 'messages',
                label: t('telegram.analytics.charts.messages'),
                color: '#38bdf8',
                values: buckets.map((bucket) => bucket.messages),
            },
            {
                key: 'views',
                label: t('telegram.analytics.charts.views'),
                color: '#f97316',
                values: buckets.map((bucket) => bucket.views),
            },
            {
                key: 'interactions',
                label: t('telegram.analytics.charts.interactions'),
                color: '#22c55e',
                values: buckets.map((bucket) => bucket.interactions),
            },
        ];
    });

    const displayedTrendSeries = computed(() =>
        trendSeries.value.filter((series) => visibleSeries.value[series.key]),
    );

    const chartMax = computed(() =>
        Math.max(
            trendMax.value,
            ...displayedTrendSeries.value.flatMap((series) => series.values),
            1,
        ),
    );

    const points = (values: number[]) => {
        if (values.length === 0) {
            return '';
        }

        const max = chartMax.value;
        const step = values.length > 1 ? chartInnerWidth / (values.length - 1) : 0;

        return values
            .map((value, index) => {
                const x = padding.left + step * index;
                const normalized = max > 0 ? value / max : 0;
                const y = padding.top + chartInnerHeight - normalized * chartInnerHeight;

                return `${index === 0 ? 'M' : 'L'} ${x.toFixed(2)} ${y.toFixed(2)}`;
            })
            .join(' ');
    };

    const pointDots = (values: number[]) => {
        const max = chartMax.value;
        const step = values.length > 1 ? chartInnerWidth / (values.length - 1) : 0;

        return values.map((value, index) => {
            const x = padding.left + step * index;
            const normalized = max > 0 ? value / max : 0;
            const y = padding.top + chartInnerHeight - normalized * chartInnerHeight;

            return {
                cx: x,
                cy: y,
                value,
            };
        });
    };

    const statCards = computed(() => {
        const summary = payload.value?.summary.totals;
        const previous = previousPayload.value?.summary.totals ?? null;

        if (!summary) {
            return [];
        }

        return [
            {
                label: t('telegram.analytics.stats.messages'),
                value: summary.messages,
                delta: formatDelta(summary.messages, previous?.messages ?? null),
            },
            {
                label: t('telegram.analytics.stats.views'),
                value: summary.views,
                delta: formatDelta(summary.views, previous?.views ?? null),
            },
            {
                label: t('telegram.analytics.stats.forwards'),
                value: summary.forwards,
                delta: formatDelta(summary.forwards, previous?.forwards ?? null),
            },
            {
                label: t('telegram.analytics.stats.replies'),
                value: summary.replies,
                delta: formatDelta(summary.replies, previous?.replies ?? null),
            },
            {
                label: t('telegram.analytics.stats.reactions'),
                value: summary.reactions,
                delta: formatDelta(summary.reactions, previous?.reactions ?? null),
            },
            {
                label: t('telegram.analytics.stats.mediaPosts'),
                value: summary.mediaPosts,
                delta: formatDelta(summary.mediaPosts, previous?.mediaPosts ?? null),
            },
            {
                label: t('telegram.analytics.stats.avgViewsPerPost'),
                value: summary.avgViewsPerPost,
                delta: formatDelta(summary.avgViewsPerPost, previous?.avgViewsPerPost ?? null),
            },
            {
                label: t('telegram.analytics.stats.avgInteractionsPerPost'),
                value: summary.avgInteractionsPerPost,
                delta: formatDelta(summary.avgInteractionsPerPost, previous?.avgInteractionsPerPost ?? null),
            },
            {
                label: t('telegram.analytics.stats.uniqueAuthors'),
                value: summary.uniqueAuthors,
                delta: formatDelta(summary.uniqueAuthors, previous?.uniqueAuthors ?? null),
            },
        ];
    });

    const maxDistribution = computed(() => {
        const mediaMax = Math.max(...(payload.value?.summary.topMedia ?? []).map((item) => item.count), 1);
        const reactionMax = Math.max(...(payload.value?.summary.topReactions ?? []).map((item) => item.count), 1);

        return Math.max(mediaMax, reactionMax, 1);
    });

    const funnelStages = computed(() => payload.value?.summary.funnel?.stages ?? []);
    const funnelMax = computed(() => Math.max(1, ...funnelStages.value.map((stage) => stage.value)));
    const funnelWidth = (value: number): string => `${Math.max(4, (value / funnelMax.value) * 100)}%`;
    const funnelStageLabel = (key: string): string => {
        const map: Record<string, string> = {
            messages: t('telegram.analytics.charts.funnelMessages'),
            views: t('telegram.analytics.charts.funnelViewed'),
            interactions: t('telegram.analytics.charts.funnelInteracted'),
            reactions: t('telegram.analytics.charts.funnelReacted'),
        };

        return map[key] ?? key;
    };

    const audience = computed(() => payload.value?.summary.audience ?? null);
    const audienceCards = computed(() => {
        if (!audience.value) {
            return [];
        }

        return [
            {
                label: t('telegram.analytics.stats.activeAuthors'),
                value: audience.value.activeAuthors,
            },
            {
                label: t('telegram.analytics.stats.singleMessageAuthors'),
                value: audience.value.singleMessageAuthors,
            },
            {
                label: t('telegram.analytics.stats.returningAuthors'),
                value: audience.value.returningAuthors,
            },
            {
                label: t('telegram.analytics.stats.topAuthorShare'),
                value: `${audience.value.topAuthorShare}%`,
            },
            {
                label: t('telegram.analytics.stats.top5AuthorsShare'),
                value: `${audience.value.top5AuthorsShare}%`,
            },
            {
                label: t('telegram.analytics.stats.concentrationIndex'),
                value: audience.value.concentrationIndex,
            },
        ];
    });

    const fraudSignals = computed(() => payload.value?.summary.fraudSignals ?? null);
    const fraudRiskLevelLabel = computed(() => t(`telegram.analytics.fraud.level.${fraudSignals.value?.riskLevel ?? 'low'}`));
    const fraudRiskBadgeClass = computed(() => {
        const level = fraudSignals.value?.riskLevel;
        if (level === 'high') {
            return 'border-red-500/40 bg-red-500/10 text-red-300';
        }

        if (level === 'medium') {
            return 'border-amber-500/40 bg-amber-500/10 text-amber-300';
        }

        return 'border-emerald-500/40 bg-emerald-500/10 text-emerald-300';
    });
    const fraudTriggerLabel = (key: string): string => t(`telegram.analytics.fraud.trigger.${key}`);
    const fraudReasonLabel = (key: string): string => t(`telegram.analytics.fraud.reason.${key}`);

    const opinionLeaders = computed(() => payload.value?.summary.opinionLeaders ?? []);
    const opinionLeadersDaily = computed(() => payload.value?.summary.opinionLeadersDaily ?? []);
    const hasOpinionLeaders = computed(() => opinionLeaders.value.length > 1);
    const leaderMaxScore = computed(() => Math.max(1, ...opinionLeaders.value.map((item) => item.score)));
    const leaderMaxInteractions = computed(() => Math.max(1, ...opinionLeaders.value.map((item) => item.interactions)));
    const leaderScoreWidth = (score: number): string => `${Math.max(4, (score / leaderMaxScore.value) * 100)}%`;
    const leaderInteractionsWidth = (interactions: number): string =>
        `${Math.max(4, (interactions / leaderMaxInteractions.value) * 100)}%`;
    const leaderChartWidth = 920;
    const leaderChartHeight = 280;
    const leaderChartPadding = {
        top: 24,
        right: 24,
        bottom: 58,
        left: 20,
    };
    const leaderChartInnerWidth = leaderChartWidth - leaderChartPadding.left - leaderChartPadding.right;
    const leaderChartInnerHeight = leaderChartHeight - leaderChartPadding.top - leaderChartPadding.bottom;
    const leaderHoveredIndex = ref<number | null>(null);
    const leaderColorPalette = ['#38bdf8', '#22c55e', '#f97316', '#eab308', '#ec4899', '#a78bfa', '#14b8a6', '#ef4444'];
    const visibleLeaderSeries = ref<Record<string, boolean>>({});

    const leaderX = (index: number): number => {
        const count = leaderDayAxis.value.length;
        const step = count > 1 ? leaderChartInnerWidth / (count - 1) : 0;

        return leaderChartPadding.left + step * index;
    };

    const opinionLeadersDailyByDay = computed(() => {
        const groups: Array<{
            dayKey: string;
            dayLabel: string;
            items: typeof opinionLeadersDaily.value;
        }> = [];
        const map = new Map<string, number>();

        for (const item of opinionLeadersDaily.value) {
            if (!map.has(item.dayKey)) {
                map.set(item.dayKey, groups.length);
                groups.push({
                    dayKey: item.dayKey,
                    dayLabel: item.dayLabel,
                    items: [],
                });
            }

            const index = map.get(item.dayKey);
            if (index === undefined) {
                continue;
            }

            groups[index].items.push(item);
        }

        return groups;
    });

    const leaderDayAxis = computed(() => opinionLeadersDailyByDay.value.map((group) => ({
        dayKey: group.dayKey,
        dayLabel: group.dayLabel,
    })));

    const leaderSeries = computed(() =>
        opinionLeaders.value.map((leader, index) => {
            const valuesByDay = new Map<string, number>();
            for (const row of opinionLeadersDaily.value) {
                if (row.authorKey !== leader.authorKey) {
                    continue;
                }

                valuesByDay.set(row.dayKey, Number(row.score) || 0);
            }

            const label = (leader.authorLabel || `ID ${leader.authorId ?? '-'}`).trim();

            return {
                key: leader.authorKey,
                label,
                color: leaderColorPalette[index % leaderColorPalette.length],
                values: leaderDayAxis.value.map((day) => valuesByDay.get(day.dayKey) ?? 0),
            };
        }),
    );

    const displayedLeaderSeries = computed(() =>
        leaderSeries.value.filter((series) => visibleLeaderSeries.value[series.key] !== false),
    );

    const leaderChartMax = computed(() => Math.max(1, ...displayedLeaderSeries.value.flatMap((series) => series.values)));
    const leaderHoverEntries = computed(() => {
        if (leaderHoveredIndex.value === null) {
            return [];
        }

        return displayedLeaderSeries.value
            .map((series) => ({
                key: series.key,
                label: series.label,
                color: series.color,
                value: series.values[leaderHoveredIndex.value ?? 0] ?? 0,
            }))
            .sort((left, right) => right.value - left.value);
    });
    const leaderHoverCardWidth = 250;
    const leaderHoverCardHeight = computed(() => 36 + leaderHoverEntries.value.length * 16);
    const leaderHoverCardX = computed(() => {
        if (leaderHoveredIndex.value === null) {
            return leaderChartPadding.left;
        }

        const x = leaderX(leaderHoveredIndex.value) - leaderHoverCardWidth / 2;

        return Math.max(leaderChartPadding.left, Math.min(x, leaderChartWidth - leaderChartPadding.right - leaderHoverCardWidth));
    });
    const leaderHoverDayLabel = computed(() => {
        if (leaderHoveredIndex.value === null) {
            return '';
        }

        return leaderDayAxis.value[leaderHoveredIndex.value]?.dayLabel ?? '';
    });
    const leaderHoverX = computed(() =>
        leaderHoveredIndex.value === null ? leaderChartPadding.left : leaderX(leaderHoveredIndex.value),
    );

    const leaderPoints = (values: number[]): string => {
        if (values.length === 0) {
            return '';
        }

        return values
            .map((value, index) => {
                const x = leaderX(index);
                const normalized = leaderChartMax.value > 0 ? value / leaderChartMax.value : 0;
                const y = leaderChartPadding.top + leaderChartInnerHeight - normalized * leaderChartInnerHeight;

                return `${index === 0 ? 'M' : 'L'} ${x.toFixed(2)} ${y.toFixed(2)}`;
            })
            .join(' ');
    };

    const leaderDots = (values: number[]) =>
        values.map((value, index) => {
            const x = leaderX(index);
            const normalized = leaderChartMax.value > 0 ? value / leaderChartMax.value : 0;
            const y = leaderChartPadding.top + leaderChartInnerHeight - normalized * leaderChartInnerHeight;

            return { x, y, value };
        });

    const toggleLeaderSeries = (key: string): void => {
        if (visibleLeaderSeries.value[key] === false) {
            visibleLeaderSeries.value[key] = true;

            return;
        }

        const activeCount = leaderSeries.value.filter((series) => visibleLeaderSeries.value[series.key] !== false).length;
        if (activeCount <= 1) {
            return;
        }

        visibleLeaderSeries.value[key] = false;
    };

    const xForIndex = (index: number): number => {
        const count = timeline.value.length;
        const step = count > 1 ? chartInnerWidth / (count - 1) : 0;

        return padding.left + step * index;
    };

    const hoverZone = (index: number) => {
        const count = timeline.value.length;
        if (count <= 1) {
            return {
                x: padding.left,
                width: chartInnerWidth,
            };
        }

        const step = chartInnerWidth / (count - 1);
        const start = padding.left + step * (index - 0.5);

        return {
            x: Math.max(padding.left, start),
            width: step,
        };
    };

    const hoveredBucket = computed(() => {
        if (hoveredIndex.value === null) {
            return null;
        }

        return timeline.value[hoveredIndex.value] ?? null;
    });

    const hoverEntries = computed(() => {
        if (hoveredIndex.value === null) {
            return [];
        }

        return displayedTrendSeries.value.map((series) => ({
            key: series.key,
            label: series.label,
            color: series.color,
            value: series.values[hoveredIndex.value ?? 0] ?? 0,
        }));
    });

    const hoverCardWidth = 206;

    const hoverCardHeight = computed(() => 38 + hoverEntries.value.length * 16);

    const hoverCardX = computed(() => {
        if (hoveredIndex.value === null) {
            return padding.left;
        }

        const x = xForIndex(hoveredIndex.value) - hoverCardWidth / 2;

        return Math.max(padding.left, Math.min(x, chartWidth - padding.right - hoverCardWidth));
    });

    const hoverCardY = padding.top + 10;

    const yTicks = computed(() => {
        const max = chartMax.value;
        const marks = [1, 0.75, 0.5, 0.25, 0];

        return marks.map((ratio) => ({
            y: padding.top + chartInnerHeight * (1 - ratio),
            value: Math.round(max * ratio),
        }));
    });

    const toggleSeries = (key: TrendSeriesKey) => {
        if (!visibleSeries.value[key]) {
            visibleSeries.value[key] = true;

            return;
        }

        const activeCount = Object.values(visibleSeries.value).filter(Boolean).length;
        if (activeCount <= 1) {
            return;
        }

        visibleSeries.value[key] = false;
    };

    return {
        t,
        PERIODS,
        PRIORITIES,
        form,
        loading,
        comparisonLoading,
        error,
        payload,
        previousPayload,
        periodLabel,
        dateLimits,
        trendMax,
        totalMessages,
        activePriority,
        applyPreset,
        setPriority,
        loadAnalytics,
        openReport,
        downloadReport,
        priorityLabel,
        analyticsPanelCollapsed,
        canLoadAnalytics,
        canUseReportActions,
        groupInfo,
        groupTypeLabel,
        mediaLabel,
        formatNumber,
        formatDate,
        timeline,
        chartWidth,
        chartHeight,
        padding,
        chartInnerWidth,
        chartInnerHeight,
        hoveredIndex,
        visibleSeries,
        trendSeries,
        displayedTrendSeries,
        chartMax,
        points,
        pointDots,
        statCards,
        maxDistribution,
        funnelStages,
        funnelMax,
        funnelWidth,
        funnelStageLabel,
        audience,
        audienceCards,
        fraudSignals,
        fraudRiskLevelLabel,
        fraudRiskBadgeClass,
        fraudTriggerLabel,
        fraudReasonLabel,
        opinionLeaders,
        opinionLeadersDaily,
        hasOpinionLeaders,
        leaderMaxScore,
        leaderMaxInteractions,
        leaderScoreWidth,
        leaderInteractionsWidth,
        leaderChartWidth,
        leaderChartHeight,
        leaderChartPadding,
        leaderChartInnerWidth,
        leaderChartInnerHeight,
        leaderHoveredIndex,
        leaderColorPalette,
        visibleLeaderSeries,
        leaderX,
        opinionLeadersDailyByDay,
        leaderDayAxis,
        leaderSeries,
        displayedLeaderSeries,
        leaderChartMax,
        leaderHoverEntries,
        leaderHoverCardWidth,
        leaderHoverCardHeight,
        leaderHoverCardX,
        leaderHoverDayLabel,
        leaderHoverX,
        leaderPoints,
        leaderDots,
        toggleLeaderSeries,
        xForIndex,
        hoverZone,
        hoveredBucket,
        hoverEntries,
        hoverCardWidth,
        hoverCardHeight,
        hoverCardX,
        hoverCardY,
        yTicks,
        toggleSeries,
    };
};
