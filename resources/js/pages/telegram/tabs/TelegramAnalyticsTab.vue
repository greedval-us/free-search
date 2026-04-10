<script setup lang="ts">
import { BarChart3, ChevronDown, ChevronUp, FileText, RefreshCw, Settings } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useTelegramAnalytics } from '../composables/useTelegramAnalytics';

const { t } = useI18n();

const {
    PERIODS,
    PRIORITIES,
    form,
    loading,
    error,
    payload,
    periodLabel,
    dateLimits,
    trendMax,
    totalMessages,
    activePriority,
    applyPreset,
    setPriority,
    loadAnalytics,
    openReport,
} = useTelegramAnalytics(t);

const priorityLabel = (priority: string) => t(`telegram.analytics.priority.${priority}`);
const analyticsPanelCollapsed = ref(false);
const canLoadAnalytics = computed(() => form.chatUsername.trim().length > 0);
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

type TrendSeriesKey = 'messages' | 'views' | 'interactions';

const visibleSeries = ref<Record<TrendSeriesKey, boolean>>({
    messages: true,
    views: true,
    interactions: true,
});

const trendSeries = computed(() => {
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
    trendSeries.value.filter((series) => visibleSeries.value[series.key as TrendSeriesKey])
);

const chartMax = computed(() =>
    Math.max(
        trendMax.value,
        ...displayedTrendSeries.value.flatMap((series) => series.values),
        1
    )
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

    if (!summary) {
        return [];
    }

    return [
        {
            label: t('telegram.analytics.stats.messages'),
            value: summary.messages,
        },
        {
            label: t('telegram.analytics.stats.views'),
            value: summary.views,
        },
        {
            label: t('telegram.analytics.stats.forwards'),
            value: summary.forwards,
        },
        {
            label: t('telegram.analytics.stats.replies'),
            value: summary.replies,
        },
        {
            label: t('telegram.analytics.stats.reactions'),
            value: summary.reactions,
        },
        {
            label: t('telegram.analytics.stats.mediaPosts'),
            value: summary.mediaPosts,
        },
        {
            label: t('telegram.analytics.stats.avgViewsPerPost'),
            value: summary.avgViewsPerPost,
        },
        {
            label: t('telegram.analytics.stats.avgInteractionsPerPost'),
            value: summary.avgInteractionsPerPost,
        },
        {
            label: t('telegram.analytics.stats.uniqueAuthors'),
            value: summary.uniqueAuthors,
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
    })
);

const leaderChartMax = computed(() => Math.max(1, ...leaderSeries.value.flatMap((series) => series.values)));
const leaderHoverEntries = computed(() => {
    if (leaderHoveredIndex.value === null) {
        return [];
    }

    return leaderSeries.value
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
    leaderHoveredIndex.value === null ? leaderChartPadding.left : leaderX(leaderHoveredIndex.value)
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

onMounted(() => {
    loadAnalytics();
});
</script>

<template>
    <section class="sticky top-0 z-10 shrink-0 rounded-xl border border-sidebar-border/80 bg-card/75 p-4 shadow-xl backdrop-blur">
        <div class="flex items-center justify-between gap-3">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-sm font-semibold">
                    <BarChart3 class="h-4 w-4 text-cyan-400" />
                    <span>{{ t('telegram.analytics.title') }}</span>
                </div>
                <p class="text-xs text-muted-foreground">
                    {{ analyticsPanelCollapsed ? t('telegram.analytics.collapsed') : t('telegram.analytics.subtitle') }}
                </p>
            </div>

            <button
                type="button"
                class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-input text-sm text-foreground transition hover:bg-accent"
                @click="analyticsPanelCollapsed = !analyticsPanelCollapsed"
            >
                <ChevronDown v-if="analyticsPanelCollapsed" class="h-4 w-4" />
                <ChevronUp v-else class="h-4 w-4" />
            </button>
        </div>

        <div v-if="!analyticsPanelCollapsed" class="mt-3 space-y-3">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-12">
                <label class="block min-w-0 xl:col-span-3">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('telegram.analytics.filters.channel') }}</span>
                    <input
                        v-model="form.chatUsername"
                        type="text"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        placeholder="durov"
                    />
                </label>

                <label class="block min-w-0 xl:col-span-3">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('telegram.search.keyword') }}</span>
                    <input
                        v-model="form.keyword"
                        type="text"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        :placeholder="t('telegram.search.placeholderKeyword')"
                    />
                </label>

                <div class="min-w-0 xl:col-span-2">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('telegram.analytics.filters.period') }}</span>
                    <div class="grid h-10 grid-cols-3 gap-1 rounded-md border border-input bg-background p-1">
                        <button
                            v-for="period in PERIODS"
                            :key="period"
                            type="button"
                            class="cursor-pointer rounded-md px-2 text-xs transition"
                            :class="form.periodDays === period && !form.dateFrom && !form.dateTo
                                ? 'bg-cyan-400/15 text-cyan-200'
                                : 'text-foreground hover:bg-accent'"
                            @click="applyPreset(period)"
                        >
                            {{ period }}
                        </button>
                    </div>
                </div>

                <label class="block min-w-0 xl:col-span-2">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('telegram.analytics.filters.from') }}</span>
                    <input
                        v-model="form.dateFrom"
                        type="date"
                        :min="dateLimits.fromMin ?? undefined"
                        :max="dateLimits.fromMax ?? undefined"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    />
                </label>

                <label class="block min-w-0 xl:col-span-2">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('telegram.analytics.filters.to') }}</span>
                    <input
                        v-model="form.dateTo"
                        type="date"
                        :min="dateLimits.toMin ?? undefined"
                        :max="dateLimits.toMax ?? undefined"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    />
                </label>
            </div>

            <div class="flex flex-wrap items-end justify-between gap-3 rounded-md border border-border/70 bg-background/60 p-2.5">
                <div class="min-w-0 flex-1">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                        {{ t('telegram.analytics.priority.title') }}
                    </p>
                    <div class="mt-1 flex flex-wrap gap-1">
                        <button
                            v-for="priority in PRIORITIES"
                            :key="priority"
                            type="button"
                            class="h-8 cursor-pointer rounded-md border px-2.5 text-xs font-medium transition"
                            :class="form.scorePriority === priority
                                ? 'border-cyan-400/50 bg-cyan-400/15 text-cyan-200'
                                : 'border-input bg-background text-foreground hover:bg-accent'"
                            @click="setPriority(priority)"
                        >
                            {{ priorityLabel(priority) }}
                        </button>
                    </div>
                </div>

                <div class="flex w-full flex-wrap justify-end gap-2 md:w-auto">
                    <button
                        type="button"
                        class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md border border-slate-700 bg-slate-900/80 px-3 text-sm text-slate-200 transition hover:border-cyan-300/40 hover:text-cyan-100"
                        :class="{ 'border-cyan-400/50 bg-cyan-400/20 text-cyan-300': !analyticsPanelCollapsed }"
                        @click="analyticsPanelCollapsed = true"
                    >
                        <Settings class="h-4 w-4" />
                        {{ t('telegram.analytics.hideSettings') }}
                    </button>

                    <button
                        type="button"
                        :disabled="loading || !canLoadAnalytics"
                        class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md border border-input bg-background px-4 text-sm font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                        @click="loadAnalytics"
                    >
                        <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': loading }" />
                        {{ loading ? t('telegram.analytics.loading') : t('telegram.analytics.refresh') }}
                    </button>

                    <button
                        type="button"
                        :disabled="!canLoadAnalytics"
                        class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="openReport"
                    >
                        <FileText class="h-4 w-4" />
                        {{ t('telegram.analytics.report') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
            <span class="rounded-full border border-border px-2 py-1">{{ periodLabel }}</span>
            <span v-if="payload" class="rounded-full border border-border px-2 py-1">
                {{ payload.range.label }}
            </span>
            <span v-if="payload?.range.keyword" class="rounded-full border border-border px-2 py-1">
                {{ t('telegram.search.keyword') }}: {{ payload.range.keyword }}
            </span>
            <span v-if="payload" class="rounded-full border border-border px-2 py-1">
                {{ t('telegram.analytics.priority.title') }}: {{ priorityLabel(activePriority) }}
            </span>
            <span v-if="payload" class="rounded-full border border-border px-2 py-1">
                {{ t('telegram.analytics.priority.formula') }}:
                {{ payload.score.weights.views }}*V + {{ payload.score.weights.forwards }}*F + {{ payload.score.weights.replies }}*R + {{ payload.score.weights.reactions }}*Re + {{ payload.score.weights.gifts }}*G
            </span>
        </div>

        <p v-if="error" class="mt-3 text-sm text-destructive">
            {{ error }}
        </p>
    </section>

    <section class="telegram-scroll flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto overscroll-contain pb-1 pr-1">
        <div
            v-if="loading && !payload"
            class="rounded-xl border border-sidebar-border/80 bg-card/70 p-6 text-center text-sm text-muted-foreground shadow-xl backdrop-blur"
        >
            {{ t('telegram.analytics.loading') }}
        </div>

        <template v-else-if="payload">
            <article
                v-if="groupInfo"
                class="rounded-xl border border-sidebar-border/80 bg-card/75 p-4 shadow-xl backdrop-blur"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="truncate text-sm font-semibold">{{ groupInfo.title || payload.range.chatUsername }}</h3>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ t('telegram.analytics.group.type') }}: {{ groupTypeLabel(groupInfo.type) }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs">
                        <span v-if="groupInfo.username" class="rounded-full border border-border px-2 py-1">
                            @{{ groupInfo.username }}
                        </span>
                        <span class="rounded-full border border-border px-2 py-1">
                            {{ t('telegram.analytics.group.participants') }}:
                            {{ groupInfo.participantsCount === null ? '-' : formatNumber(groupInfo.participantsCount) }}
                        </span>
                        <span v-if="groupInfo.onlineCount !== null" class="rounded-full border border-border px-2 py-1">
                            {{ t('telegram.analytics.group.online') }}: {{ formatNumber(groupInfo.onlineCount) }}
                        </span>
                        <span v-if="groupInfo.verified" class="rounded-full border border-emerald-500/40 bg-emerald-500/10 px-2 py-1 text-emerald-300">
                            {{ t('telegram.analytics.group.verified') }}
                        </span>
                    </div>
                </div>

                <p v-if="groupInfo.description" class="mt-3 line-clamp-3 text-sm text-muted-foreground">
                    {{ groupInfo.description }}
                </p>

                <div class="mt-3 flex flex-wrap gap-2 text-xs text-muted-foreground">
                    <span v-if="groupInfo.createdAt" class="rounded-full border border-border px-2 py-1">
                        {{ t('telegram.analytics.group.createdAt') }}: {{ formatDate(groupInfo.createdAt) }}
                    </span>
                    <span v-if="groupInfo.canViewStats" class="rounded-full border border-border px-2 py-1">
                        {{ t('telegram.analytics.group.statsAvailable') }}
                    </span>
                    <span v-if="groupInfo.linkedChatId" class="rounded-full border border-border px-2 py-1">
                        {{ t('telegram.analytics.group.linkedChatId') }}: {{ formatNumber(groupInfo.linkedChatId) }}
                    </span>
                    <span v-if="groupInfo.restricted" class="rounded-full border border-amber-500/40 bg-amber-500/10 px-2 py-1 text-amber-300">
                        {{ t('telegram.analytics.group.restricted') }}
                    </span>
                    <span v-if="groupInfo.scam" class="rounded-full border border-red-500/40 bg-red-500/10 px-2 py-1 text-red-300">
                        {{ t('telegram.analytics.group.scam') }}
                    </span>
                </div>
            </article>

            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="card in statCards"
                    :key="card.label"
                    class="rounded-xl border border-sidebar-border/80 bg-card/75 p-4 shadow-xl backdrop-blur"
                >
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">{{ card.label }}</p>
                    <p class="mt-2 text-2xl font-semibold">
                        {{ typeof card.value === 'number' ? formatNumber(card.value) : card.value }}
                    </p>
                </article>
            </div>

            <div class="grid gap-4 xl:grid-cols-2">
                <article class="rounded-xl border border-sidebar-border/80 bg-card/75 p-4 shadow-xl backdrop-blur">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold">{{ t('telegram.analytics.charts.funnel') }}</h3>
                            <p class="text-xs text-muted-foreground">{{ t('telegram.analytics.charts.funnelHint') }}</p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-3">
                        <article
                            v-for="(stage, index) in funnelStages"
                            :key="`funnel-${stage.key}`"
                            class="rounded-lg border border-border/70 bg-background/70 p-3"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-semibold">{{ funnelStageLabel(stage.key) }}</p>
                                <span class="text-xs font-semibold">{{ formatNumber(stage.value) }}</span>
                            </div>
                            <div class="mt-2 h-2 rounded-full bg-muted">
                                <div
                                    class="h-2 rounded-full bg-cyan-400"
                                    :style="{ width: funnelWidth(stage.value) }"
                                />
                            </div>
                            <div class="mt-2 flex flex-wrap gap-2 text-[11px] text-muted-foreground">
                                <span>
                                    {{ t('telegram.analytics.charts.fromPrevious') }}:
                                    {{ formatNumber(stage.value) }} / {{ formatNumber(index === 0 ? stage.value : (funnelStages[index - 1]?.value ?? 0)) }}
                                    ({{ stage.conversionFromPrevious }}%)
                                </span>
                                <span>
                                    {{ t('telegram.analytics.charts.fromStart') }}:
                                    {{ formatNumber(stage.value) }} / {{ formatNumber(funnelStages[0]?.value ?? 0) }}
                                    ({{ stage.conversionFromStart }}%)
                                </span>
                            </div>
                        </article>
                    </div>
                </article>

                <article class="rounded-xl border border-sidebar-border/80 bg-card/75 p-4 shadow-xl backdrop-blur">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold">{{ t('telegram.analytics.charts.audience') }}</h3>
                            <p class="text-xs text-muted-foreground">{{ t('telegram.analytics.charts.audienceHint') }}</p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-2 sm:grid-cols-2">
                        <article
                            v-for="card in audienceCards"
                            :key="`audience-${card.label}`"
                            class="rounded-lg border border-border/70 bg-background/70 p-3"
                        >
                            <p class="text-[11px] uppercase tracking-wide text-muted-foreground">{{ card.label }}</p>
                            <p class="mt-1 text-base font-semibold">{{ card.value }}</p>
                        </article>
                    </div>

                    <div v-if="audience?.mostActiveHours?.length" class="mt-4 rounded-lg border border-border/70 bg-background/70 p-3">
                        <p class="text-xs font-semibold">{{ t('telegram.analytics.charts.mostActiveHours') }}</p>
                        <div class="mt-2 grid gap-2 sm:grid-cols-3">
                            <article
                                v-for="hour in audience.mostActiveHours"
                                :key="`hour-${hour.hour}`"
                                class="rounded-md border border-border/70 bg-background/80 p-2 text-xs"
                            >
                                <p class="font-semibold">{{ hour.label }}</p>
                                <p class="mt-1 text-muted-foreground">
                                    {{ t('telegram.analytics.stats.messages') }}: {{ formatNumber(hour.messages) }}
                                </p>
                            </article>
                        </div>
                    </div>
                </article>
            </div>

            <div class="grid gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(340px,1fr)]">
                <article class="rounded-xl border border-sidebar-border/80 bg-card/75 p-4 shadow-xl backdrop-blur">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold">{{ t('telegram.analytics.charts.activity') }}</h3>
                            <p class="text-xs text-muted-foreground">{{ t('telegram.analytics.charts.activityHint') }}</p>
                        </div>
                        <span class="rounded-full border border-border px-2 py-1 text-xs text-muted-foreground">
                            {{ totalMessages }} {{ t('telegram.analytics.stats.messagesShort') }}
                        </span>
                    </div>

                    <div class="mt-4 overflow-hidden rounded-lg border border-border/70 bg-background/80 p-3">
                        <svg
                            viewBox="0 0 920 280"
                            class="h-auto w-full"
                            role="img"
                            :aria-label="t('telegram.analytics.charts.activity')"
                        >
                            <defs>
                                <linearGradient id="telegram-trend-fill" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#38bdf8" stop-opacity="0.18" />
                                    <stop offset="100%" stop-color="#38bdf8" stop-opacity="0.02" />
                                </linearGradient>
                            </defs>

                            <g stroke="currentColor" stroke-opacity="0.08">
                                <line
                                    v-for="tick in yTicks"
                                    :key="`grid-${tick.value}-${tick.y}`"
                                    :x1="padding.left"
                                    :y1="tick.y"
                                    :x2="chartWidth - padding.right"
                                    :y2="tick.y"
                                />
                                <line :x1="padding.left" :y1="padding.top" :x2="padding.left" :y2="chartHeight - padding.bottom" />
                            </g>

                            <g v-if="timeline.length > 0" stroke-linecap="round" stroke-linejoin="round">
                                <path
                                    v-for="series in displayedTrendSeries"
                                    :key="series.key"
                                    :d="points(series.values)"
                                    fill="none"
                                    :stroke="series.color"
                                    stroke-width="3"
                                />
                                <g v-for="series in displayedTrendSeries" :key="`${series.key}-dots`">
                                    <circle
                                        v-for="dot in pointDots(series.values)"
                                        :key="`${series.key}-${dot.value}-${dot.cx}`"
                                        :cx="dot.cx"
                                        :cy="dot.cy"
                                        r="3.5"
                                        :fill="series.color"
                                    />
                                </g>
                            </g>

                            <g v-if="timeline.length > 0">
                                <rect
                                    v-for="(bucket, index) in timeline"
                                    :key="`hover-zone-${bucket.key}`"
                                    :x="hoverZone(index).x"
                                    :y="padding.top"
                                    :width="hoverZone(index).width"
                                    :height="chartInnerHeight"
                                    fill="transparent"
                                    @mouseenter="hoveredIndex = index"
                                    @mouseleave="hoveredIndex = null"
                                />
                            </g>

                            <g v-if="hoveredBucket && hoveredIndex !== null">
                                <line
                                    :x1="xForIndex(hoveredIndex)"
                                    :y1="padding.top"
                                    :x2="xForIndex(hoveredIndex)"
                                    :y2="chartHeight - padding.bottom"
                                    stroke="#38bdf8"
                                    stroke-opacity="0.55"
                                    stroke-dasharray="4 4"
                                />

                                <rect
                                    :x="hoverCardX"
                                    :y="hoverCardY"
                                    :width="hoverCardWidth"
                                    :height="hoverCardHeight"
                                    rx="8"
                                    fill="#0f172a"
                                    fill-opacity="0.94"
                                />

                                <text
                                    :x="hoverCardX + 10"
                                    :y="hoverCardY + 16"
                                    fill="#e2e8f0"
                                    class="text-[11px] font-semibold"
                                >
                                    {{ hoveredBucket.label }}
                                </text>

                                <g v-for="(entry, row) in hoverEntries" :key="`tooltip-${entry.key}`">
                                    <circle :cx="hoverCardX + 12" :cy="hoverCardY + 29 + row * 16" r="3" :fill="entry.color" />
                                    <text :x="hoverCardX + 20" :y="hoverCardY + 32 + row * 16" fill="#cbd5e1" class="text-[10px]">
                                        {{ entry.label }}: {{ formatNumber(entry.value) }}
                                    </text>
                                </g>
                            </g>

                            <g v-if="timeline.length > 0">
                                <text
                                    v-for="(bucket, index) in timeline"
                                    :key="bucket.key"
                                    :x="padding.left + (timeline.length > 1 ? (chartInnerWidth / (timeline.length - 1)) * index : 0)"
                                    :y="chartHeight - 12"
                                    text-anchor="middle"
                                    class="fill-muted-foreground text-[11px]"
                                >
                                    {{ bucket.label }}
                                </text>
                            </g>

                            <g>
                                <text
                                    v-for="tick in yTicks"
                                    :key="`tick-${tick.value}-${tick.y}`"
                                    :x="chartWidth - padding.right + 2"
                                    :y="tick.y + 4"
                                    class="fill-muted-foreground text-[10px]"
                                >
                                    {{ formatNumber(tick.value) }}
                                </text>
                            </g>
                        </svg>
                    </div>

                    <div class="mt-4 grid gap-2 md:grid-cols-3">
                        <button
                            v-for="series in trendSeries"
                            :key="series.key"
                            type="button"
                            class="rounded-lg border px-3 py-2 text-left transition"
                            :class="visibleSeries[series.key as TrendSeriesKey]
                                ? 'cursor-pointer border-border/70 bg-background/80 hover:bg-accent/50'
                                : 'cursor-pointer border-border/40 bg-background/40 opacity-55 hover:opacity-80'"
                            @click="toggleSeries(series.key as TrendSeriesKey)"
                        >
                            <p class="text-[11px] uppercase tracking-wide text-muted-foreground">
                                {{ series.label }}
                            </p>
                            <p class="mt-1 text-sm font-semibold">{{ formatNumber(series.values.reduce((acc, value) => acc + value, 0)) }}</p>
                        </button>
                    </div>
                </article>

                <article class="rounded-xl border border-sidebar-border/80 bg-card/75 p-4 shadow-xl backdrop-blur">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold">{{ t('telegram.analytics.charts.distribution') }}</h3>
                        <span class="text-xs text-muted-foreground">{{ t('telegram.analytics.charts.distributionHint') }}</span>
                    </div>

                    <div class="mt-4 space-y-5">
                        <div>
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                {{ t('telegram.analytics.charts.media') }}
                            </h4>
                            <div class="mt-3 space-y-3">
                                <div v-for="item in payload.summary.topMedia" :key="item.key" class="space-y-1">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="font-medium">{{ mediaLabel(item.key) }}</span>
                                        <span class="text-muted-foreground">{{ formatNumber(item.count) }} / {{ item.share }}%</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-muted">
                                        <div
                                            class="h-2 rounded-full bg-cyan-400"
                                            :style="{ width: `${Math.max(6, (item.count / maxDistribution) * 100)}%` }"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                {{ t('telegram.analytics.charts.reactions') }}
                            </h4>
                            <div class="mt-3 space-y-3">
                                <div v-for="item in payload.summary.topReactions" :key="item.label" class="space-y-1">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="font-medium">{{ item.label }}</span>
                                        <span class="text-muted-foreground">{{ formatNumber(item.count) }} / {{ item.share }}%</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-muted">
                                        <div
                                            class="h-2 rounded-full bg-emerald-400"
                                            :style="{ width: `${Math.max(6, (item.count / maxDistribution) * 100)}%` }"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <article
                v-if="hasOpinionLeaders"
                class="rounded-xl border border-sidebar-border/80 bg-card/75 p-4 shadow-xl backdrop-blur"
            >
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold">{{ t('telegram.analytics.charts.opinionLeaders') }}</h3>
                        <p class="text-xs text-muted-foreground">{{ t('telegram.analytics.charts.opinionLeadersHint') }}</p>
                    </div>
                    <span class="rounded-full border border-border px-2 py-1 text-xs text-muted-foreground">
                        {{ opinionLeaders.length }}
                    </span>
                </div>

                <div class="mt-4 overflow-hidden rounded-lg border border-border/70 bg-background/80 p-3">
                    <svg
                        viewBox="0 0 920 280"
                        class="h-auto w-full"
                        role="img"
                        :aria-label="t('telegram.analytics.charts.opinionLeaders')"
                    >
                        <g stroke="currentColor" stroke-opacity="0.08">
                            <line
                                :x1="leaderChartPadding.left"
                                :y1="leaderChartPadding.top"
                                :x2="leaderChartPadding.left"
                                :y2="leaderChartHeight - leaderChartPadding.bottom"
                            />
                            <line
                                :x1="leaderChartPadding.left"
                                :y1="leaderChartHeight - leaderChartPadding.bottom"
                                :x2="leaderChartWidth - leaderChartPadding.right"
                                :y2="leaderChartHeight - leaderChartPadding.bottom"
                            />
                        </g>

                        <g stroke-linecap="round" stroke-linejoin="round">
                            <path
                                v-for="series in leaderSeries"
                                :key="series.key"
                                :d="leaderPoints(series.values)"
                                fill="none"
                                :stroke="series.color"
                                stroke-width="3"
                            />
                            <g v-for="series in leaderSeries" :key="`${series.key}-dots`">
                                <circle
                                    v-for="(dot, index) in leaderDots(series.values)"
                                    :key="`${series.key}-${index}`"
                                    :cx="dot.x"
                                    :cy="dot.y"
                                    r="4"
                                    :fill="series.color"
                                />
                            </g>
                        </g>

                        <g>
                            <rect
                                v-for="(day, index) in leaderDayAxis"
                                :key="`leader-zone-${day.dayKey}`"
                                :x="leaderX(index) - (leaderDayAxis.length > 1 ? leaderChartInnerWidth / (leaderDayAxis.length - 1) / 2 : leaderChartInnerWidth / 2)"
                                :y="leaderChartPadding.top"
                                :width="leaderDayAxis.length > 1 ? leaderChartInnerWidth / (leaderDayAxis.length - 1) : leaderChartInnerWidth"
                                :height="leaderChartInnerHeight"
                                fill="transparent"
                                @mouseenter="leaderHoveredIndex = index"
                                @mouseleave="leaderHoveredIndex = null"
                            />
                        </g>

                        <g v-if="leaderHoveredIndex !== null">
                            <line
                                :x1="leaderHoverX"
                                :y1="leaderChartPadding.top"
                                :x2="leaderHoverX"
                                :y2="leaderChartHeight - leaderChartPadding.bottom"
                                stroke="#38bdf8"
                                stroke-opacity="0.55"
                                stroke-dasharray="4 4"
                            />
                            <rect
                                :x="leaderHoverCardX"
                                :y="leaderChartPadding.top + 10"
                                :width="leaderHoverCardWidth"
                                :height="leaderHoverCardHeight"
                                rx="8"
                                fill="#0f172a"
                                fill-opacity="0.94"
                            />
                            <text
                                :x="leaderHoverCardX + 10"
                                :y="leaderChartPadding.top + 26"
                                fill="#e2e8f0"
                                class="text-[11px] font-semibold"
                            >
                                {{ leaderHoverDayLabel }}
                            </text>
                            <g v-for="(entry, row) in leaderHoverEntries" :key="`leader-tooltip-${entry.key}`">
                                <circle :cx="leaderHoverCardX + 12" :cy="leaderChartPadding.top + 39 + row * 16" r="3" :fill="entry.color" />
                                <text :x="leaderHoverCardX + 20" :y="leaderChartPadding.top + 42 + row * 16" fill="#cbd5e1" class="text-[10px]">
                                    {{ entry.label }}: {{ formatNumber(entry.value) }}
                                </text>
                            </g>
                        </g>

                        <g>
                            <text
                                v-for="(day, index) in leaderDayAxis"
                                :key="`leader-label-${day.dayKey}`"
                                :x="leaderX(index)"
                                :y="leaderChartHeight - 12"
                                text-anchor="middle"
                                class="fill-muted-foreground text-[11px]"
                            >
                                {{ day.dayLabel }}
                            </text>
                        </g>
                    </svg>
                </div>

                <div class="mt-4 grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                    <div
                        v-for="series in leaderSeries"
                        :key="`leader-legend-${series.key}`"
                        class="rounded-md border border-border/70 bg-background/70 px-3 py-2 text-xs"
                    >
                        <span class="inline-flex items-center gap-2 font-medium">
                            <span class="inline-block h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: series.color }" />
                            {{ series.label }}
                        </span>
                    </div>
                </div>

                <div v-if="false" class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                    <div class="space-y-3">
                        <article
                            v-for="leader in opinionLeaders"
                            :key="leader.authorKey"
                            class="rounded-lg border border-border/70 bg-background/70 p-3"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <p class="truncate text-sm font-semibold">
                                    {{ leader.authorLabel || `ID ${leader.authorId ?? '-'}` }}
                                </p>
                                <span class="text-xs text-muted-foreground">
                                    {{ t('telegram.analytics.stats.messages') }}: {{ formatNumber(leader.messages) }}
                                </span>
                            </div>

                            <div class="mt-2 space-y-2">
                                <div>
                                    <div class="mb-1 flex items-center justify-between text-[11px]">
                                        <span class="text-muted-foreground">{{ t('telegram.analytics.score') }}</span>
                                        <span class="font-medium">{{ formatNumber(leader.score) }}</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-muted">
                                        <div
                                            class="h-2 rounded-full bg-cyan-400"
                                            :style="{ width: leaderScoreWidth(leader.score) }"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <div class="mb-1 flex items-center justify-between text-[11px]">
                                        <span class="text-muted-foreground">{{ t('telegram.analytics.charts.interactions') }}</span>
                                        <span class="font-medium">{{ formatNumber(leader.interactions) }}</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-muted">
                                        <div
                                            class="h-2 rounded-full bg-emerald-400"
                                            :style="{ width: leaderInteractionsWidth(leader.interactions) }"
                                        />
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div class="rounded-lg border border-border/70 bg-background/70 p-3">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            {{ t('telegram.analytics.charts.opinionLeadersBreakdown') }}
                        </h4>
                        <div class="mt-3 space-y-2">
                            <article
                                v-for="leader in opinionLeaders"
                                :key="`${leader.authorKey}-breakdown`"
                                class="rounded-md border border-border/70 bg-background/80 p-2 text-xs"
                            >
                                <p class="truncate font-medium">{{ leader.authorLabel || `ID ${leader.authorId ?? '-'}` }}</p>
                                <p class="mt-1 text-muted-foreground">
                                    {{ t('telegram.analytics.stats.forwards') }}: {{ formatNumber(leader.forwards) }}
                                    · {{ t('telegram.analytics.stats.replies') }}: {{ formatNumber(leader.replies) }}
                                    · {{ t('telegram.analytics.stats.reactions') }}: {{ formatNumber(leader.reactions) }}
                                    · {{ t('telegram.analytics.stats.gifts') }}: {{ formatNumber(leader.gifts) }}
                                </p>
                            </article>
                        </div>
                    </div>
                </div>
            </article>

            <article
                v-if="false"
                class="rounded-xl border border-sidebar-border/80 bg-card/75 p-4 shadow-xl backdrop-blur"
            >
                <h3 class="text-sm font-semibold">{{ t('telegram.analytics.charts.opinionLeadersByDay') }}</h3>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ t('telegram.analytics.charts.opinionLeadersByDayHint') }}
                </p>

                <div class="mt-3 space-y-3">
                    <article
                        v-for="dayGroup in opinionLeadersDailyByDay"
                        :key="dayGroup.dayKey"
                        class="rounded-md border border-border/70 bg-background/80 p-2"
                    >
                        <p class="text-xs font-semibold">{{ dayGroup.dayLabel }}</p>
                        <div class="mt-2 overflow-x-auto">
                            <table class="w-full min-w-[700px] text-xs">
                                <thead>
                                    <tr class="text-left text-muted-foreground">
                                        <th class="py-1 pr-2">{{ t('telegram.analytics.charts.author') }}</th>
                                        <th class="py-1 pr-2">{{ t('telegram.analytics.stats.messages') }}</th>
                                        <th class="py-1 pr-2">{{ t('telegram.analytics.stats.forwards') }}</th>
                                        <th class="py-1 pr-2">{{ t('telegram.analytics.stats.replies') }}</th>
                                        <th class="py-1 pr-2">{{ t('telegram.analytics.stats.reactions') }}</th>
                                        <th class="py-1 pr-2">{{ t('telegram.analytics.stats.gifts') }}</th>
                                        <th class="py-1 pr-2">{{ t('telegram.analytics.charts.interactions') }}</th>
                                        <th class="py-1">{{ t('telegram.analytics.score') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in dayGroup.items"
                                        :key="`${dayGroup.dayKey}-${item.authorKey}`"
                                        class="border-t border-border/60"
                                    >
                                        <td class="py-1 pr-2">{{ item.authorLabel || `ID ${item.authorId ?? '-'}` }}</td>
                                        <td class="py-1 pr-2">{{ formatNumber(item.messages) }}</td>
                                        <td class="py-1 pr-2">{{ formatNumber(item.forwards) }}</td>
                                        <td class="py-1 pr-2">{{ formatNumber(item.replies) }}</td>
                                        <td class="py-1 pr-2">{{ formatNumber(item.reactions) }}</td>
                                        <td class="py-1 pr-2">{{ formatNumber(item.gifts) }}</td>
                                        <td class="py-1 pr-2">{{ formatNumber(item.interactions) }}</td>
                                        <td class="py-1">{{ formatNumber(item.score) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </article>
                </div>
            </article>

            <article class="rounded-xl border border-sidebar-border/80 bg-card/75 p-4 shadow-xl backdrop-blur">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold">{{ t('telegram.analytics.charts.topPosts') }}</h3>
                        <p class="text-xs text-muted-foreground">{{ t('telegram.analytics.charts.topPostsHint') }}</p>
                    </div>
                    <span class="rounded-full border border-border px-2 py-1 text-xs text-muted-foreground">
                        {{ payload.summary.topPosts.length }}
                    </span>
                </div>

                <div class="mt-4 grid gap-3">
                    <article
                        v-for="(post, index) in payload.summary.topPosts"
                        :key="post.id"
                        class="rounded-xl border border-border/70 bg-background/75 p-4"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1 space-y-2">
                                <div class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                    <span>#{{ index + 1 }}</span>
                                    <span>{{ formatDate(post.date) }}</span>
                                    <span v-if="post.mediaLabel">{{ post.mediaLabel }}</span>
                                </div>
                                <p class="line-clamp-3 text-sm leading-relaxed">
                                    {{ post.message || t('telegram.analytics.emptyPost') }}
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <span class="rounded-full border border-border px-2 py-1">{{ t('telegram.analytics.stats.views') }}: {{ formatNumber(post.views) }}</span>
                                <span class="rounded-full border border-border px-2 py-1">{{ t('telegram.analytics.stats.forwards') }}: {{ formatNumber(post.forwards) }}</span>
                                <span class="rounded-full border border-border px-2 py-1">{{ t('telegram.analytics.stats.replies') }}: {{ formatNumber(post.replies) }}</span>
                                <span class="rounded-full border border-border px-2 py-1">{{ t('telegram.analytics.stats.reactions') }}: {{ formatNumber(post.reactions) }}</span>
                                <span class="rounded-full border border-cyan-400/40 bg-cyan-400/10 px-2 py-1 text-cyan-200">
                                    {{ t('telegram.analytics.score') }}: {{ formatNumber(post.score) }}
                                </span>
                                <a
                                    v-if="post.telegramUrl"
                                    :href="post.telegramUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="cursor-pointer rounded-full border border-input px-2 py-1 text-foreground hover:bg-accent"
                                >
                                    {{ t('telegram.analytics.openTelegram') }}
                                </a>
                            </div>
                        </div>
                    </article>
                </div>
            </article>
        </template>

        <div
            v-else
            class="flex min-h-[50vh] flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/80 bg-card/70 p-8 text-center shadow-xl backdrop-blur"
        >
            <BarChart3 class="mb-4 h-14 w-14 text-muted-foreground" />
            <h3 class="text-lg font-semibold">{{ t('telegram.analytics.empty.title') }}</h3>
            <p class="mt-2 max-w-2xl text-sm text-muted-foreground">
                {{ t('telegram.analytics.empty.description') }}
            </p>
            <button
                type="button"
                class="mt-5 inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground hover:opacity-90"
                @click="loadAnalytics"
            >
                <RefreshCw class="h-4 w-4" />
                {{ t('telegram.analytics.refresh') }}
            </button>
        </div>
    </section>
</template>
