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

const formatNumber = (value: number) => new Intl.NumberFormat().format(value);

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

const chartMax = computed(() =>
    Math.max(
        trendMax.value,
        ...trendSeries.value.flatMap((series) => series.values),
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
                                <line :x1="padding.left" :y1="padding.top" :x2="padding.left" :y2="chartHeight - padding.bottom" />
                                <line :x1="padding.left" :y1="chartHeight - padding.bottom" :x2="chartWidth - padding.right" :y2="chartHeight - padding.bottom" />
                            </g>

                            <g v-if="timeline.length > 0" stroke-linecap="round" stroke-linejoin="round">
                                <path
                                    v-for="series in trendSeries"
                                    :key="series.key"
                                    :d="points(series.values)"
                                    fill="none"
                                    :stroke="series.color"
                                    stroke-width="3"
                                />
                                <g v-for="series in trendSeries" :key="`${series.key}-dots`">
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
                        </svg>
                    </div>

                    <div class="mt-4 grid gap-2 md:grid-cols-3">
                        <div
                            v-for="series in trendSeries"
                            :key="series.key"
                            class="rounded-lg border border-border/70 bg-background/80 px-3 py-2"
                        >
                            <p class="text-[11px] uppercase tracking-wide text-muted-foreground">{{ series.label }}</p>
                            <p class="mt-1 text-sm font-semibold">{{ formatNumber(series.values.reduce((acc, value) => acc + value, 0)) }}</p>
                        </div>
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
