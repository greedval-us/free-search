<script setup lang="ts">
import { BarChart3, ChevronDown, ChevronUp, Download, FileText, RefreshCw, Settings } from 'lucide-vue-next';
import { onMounted } from 'vue';
import {
    getRepeatQueryParams,
    isRepeatAutorunEnabled,
    readRepeatQueryInt,
    readRepeatQueryParam,
} from '@/composables/useRepeatQuery';
import TelegramAnalyticsEmptyState from './analytics/components/TelegramAnalyticsEmptyState.vue';
import TelegramAnalyticsStatCards from './analytics/components/TelegramAnalyticsStatCards.vue';
import TelegramAnalyticsTopPosts from './analytics/components/TelegramAnalyticsTopPosts.vue';
import { useTelegramAnalyticsTab } from '../composables/useTelegramAnalyticsTab';

const {
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
    points,
    pointDots,
    statCards,
    maxDistribution,
    funnelStages,
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
    hasOpinionLeaders,
    leaderScoreWidth,
    leaderInteractionsWidth,
    leaderChartWidth,
    leaderChartHeight,
    leaderChartPadding,
    leaderChartInnerWidth,
    leaderChartInnerHeight,
    leaderHoveredIndex,
    visibleLeaderSeries,
    leaderX,
    opinionLeadersDailyByDay,
    leaderDayAxis,
    leaderSeries,
    displayedLeaderSeries,
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
} = useTelegramAnalyticsTab();

onMounted(() => {
    const params = getRepeatQueryParams();
    if (!params) {
        return;
    }

    const tab = readRepeatQueryParam(params, ['tab']);
    if (tab !== 'analytics') {
        return;
    }

    const chatUsername = readRepeatQueryParam(params, ['chatUsername']);
    const keyword = readRepeatQueryParam(params, ['keyword']);
    const dateFrom = readRepeatQueryParam(params, ['dateFrom']);
    const dateTo = readRepeatQueryParam(params, ['dateTo']);
    const scorePriority = readRepeatQueryParam(params, ['scorePriority']);
    const periodDays = readRepeatQueryInt(params, 'periodDays');

    if (chatUsername !== '') {
        form.chatUsername = chatUsername;
    }

    if (keyword !== '') {
        form.keyword = keyword;
    }

    if (scorePriority === 'balanced' || scorePriority === 'reach' || scorePriority === 'discussion' || scorePriority === 'virality') {
        form.scorePriority = scorePriority;
    }

    if (dateFrom !== '' && dateTo !== '') {
        form.dateFrom = dateFrom;
        form.dateTo = dateTo;
    } else if (periodDays === 1 || periodDays === 3 || periodDays === 7) {
        applyPreset(periodDays);
    }

    if (isRepeatAutorunEnabled(params) && canLoadAnalytics.value) {
        void loadAnalytics();
    }
});
</script>

<template>
    <section class="sticky top-0 z-10 shrink-0 rounded-xl border border-sidebar-border/80 bg-card/75 p-4 shadow-xl backdrop-blur">
        <div class="flex items-center justify-between gap-3">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-sm font-semibold">
                    <BarChart3 class="h-4 w-4 text-cyan-400" />
                    <span>{{ t('telegram.analytics.title') }}</span>
                    <span class="group relative inline-flex">
                        <span
                            class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                            :aria-label="t('telegram.help.label')"
                        >
                            ?
                        </span>
                        <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                            {{ t('telegram.analytics.help.overview') }}
                        </span>
                    </span>
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
                        :disabled="!canUseReportActions"
                        class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="openReport"
                    >
                        <FileText class="h-4 w-4" />
                        {{ t('telegram.analytics.report') }}
                    </button>

                    <button
                        type="button"
                        :disabled="!canUseReportActions"
                        class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md border border-input bg-background px-4 text-sm font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                        @click="downloadReport"
                    >
                        <Download class="h-4 w-4" />
                        {{ t('telegram.analytics.downloadReport') }}
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
            <span v-if="payload" class="relative flex items-center gap-1 rounded-full border border-border px-2 py-1">
                <span>
                    {{ t('telegram.analytics.priority.formula') }}:
                    {{ payload.score.weights.views }}*V + {{ payload.score.weights.forwards }}*F + {{ payload.score.weights.replies }}*R + {{ payload.score.weights.reactions }}*Re + {{ payload.score.weights.gifts }}*G
                </span>
                <span class="group relative inline-flex">
                    <span
                        class="inline-flex h-4 w-4 cursor-help items-center justify-center rounded-full border border-border text-[10px] font-semibold text-muted-foreground"
                        :aria-label="t('telegram.analytics.help.label')"
                    >
                        ?
                    </span>
                    <span class="pointer-events-none absolute right-0 top-5 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                        {{ t('telegram.analytics.help.formula') }}
                    </span>
                </span>
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

            <TelegramAnalyticsStatCards :cards="statCards" />

            <article
                v-if="fraudSignals"
                class="rounded-xl border border-sidebar-border/80 bg-card/75 p-4 shadow-xl backdrop-blur"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold">{{ t('telegram.analytics.fraud.title') }}</h3>
                            <div class="group relative">
                                <span
                                    class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                                    :aria-label="t('telegram.analytics.help.label')"
                                >
                                    ?
                                </span>
                                <div class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                                    {{ t('telegram.analytics.help.antiFraud') }}
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-muted-foreground">{{ t('telegram.analytics.fraud.hint') }}</p>
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="rounded-full border border-border px-2 py-1">
                            {{ t('telegram.analytics.fraud.riskScore') }}: {{ formatNumber(fraudSignals.riskScore) }}
                        </span>
                        <span class="rounded-full border px-2 py-1" :class="fraudRiskBadgeClass">
                            {{ t('telegram.analytics.fraud.riskLevel') }}: {{ fraudRiskLevelLabel }}
                        </span>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                    <div class="rounded-lg border border-border/70 bg-background/70 p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            {{ t('telegram.analytics.fraud.triggers') }}
                        </p>
                        <div class="mt-2 space-y-2">
                            <article
                                v-for="trigger in fraudSignals.triggers"
                                :key="`fraud-trigger-${trigger.key}`"
                                class="rounded-md border border-border/70 bg-background/80 p-2 text-xs"
                            >
                                <p class="font-semibold">{{ fraudTriggerLabel(trigger.key) }}</p>
                                <p class="mt-1 text-muted-foreground">
                                    +{{ trigger.score }} В· {{ formatNumber(trigger.value) }} / {{ formatNumber(trigger.threshold) }}
                                </p>
                            </article>
                            <p v-if="fraudSignals.triggers.length === 0" class="text-xs text-muted-foreground">
                                {{ t('telegram.analytics.fraud.noTriggers') }}
                            </p>
                        </div>
                    </div>

                    <div class="rounded-lg border border-border/70 bg-background/70 p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            {{ t('telegram.analytics.fraud.suspiciousPosts') }}: {{ formatNumber(fraudSignals.suspiciousPostsCount) }}
                        </p>
                        <div class="mt-2 space-y-2">
                            <article
                                v-for="post in fraudSignals.suspiciousPosts"
                                :key="`fraud-post-${post.id}`"
                                class="rounded-md border border-border/70 bg-background/80 p-2 text-xs"
                            >
                                <p class="font-semibold">#{{ post.id }} В· {{ formatDate(post.date) }}</p>
                                <p class="mt-1 line-clamp-2 text-muted-foreground">{{ post.message || t('telegram.analytics.emptyPost') }}</p>
                                <p class="mt-1 text-muted-foreground">
                                    {{ t('telegram.analytics.fraud.riskScore') }}: {{ formatNumber(post.riskScore) }}
                                </p>
                                <p class="mt-1 text-muted-foreground">
                                    {{ post.reasons.map((reason) => fraudReasonLabel(reason)).join(' В· ') }}
                                </p>
                            </article>
                        </div>
                    </div>
                </div>
            </article>

            <div class="grid gap-4 xl:grid-cols-2">
                <article class="rounded-xl border border-sidebar-border/80 bg-card/75 p-4 shadow-xl backdrop-blur">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold">{{ t('telegram.analytics.charts.funnel') }}</h3>
                            <p class="text-xs text-muted-foreground">{{ t('telegram.analytics.charts.funnelHint') }}</p>
                        </div>
                        <div class="group relative">
                            <span
                                class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                                :aria-label="t('telegram.analytics.help.label')"
                            >
                                ?
                            </span>
                            <div class="pointer-events-none absolute right-0 top-6 z-20 hidden w-64 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                                {{ t('telegram.analytics.help.funnel') }}
                            </div>
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
                        <div class="group relative">
                            <span
                                class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                                :aria-label="t('telegram.analytics.help.label')"
                            >
                                ?
                            </span>
                            <div class="pointer-events-none absolute right-0 top-6 z-20 hidden w-64 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                                {{ t('telegram.analytics.help.audience') }}
                            </div>
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
                        <div class="flex items-center gap-2">
                            <div class="group relative">
                                <span
                                    class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                                    :aria-label="t('telegram.analytics.help.label')"
                                >
                                    ?
                                </span>
                                <div class="pointer-events-none absolute right-0 top-6 z-20 hidden w-64 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                                    {{ t('telegram.analytics.help.activity') }}
                                </div>
                            </div>
                            <span class="rounded-full border border-border px-2 py-1 text-xs text-muted-foreground">
                                {{ totalMessages }} {{ t('telegram.analytics.stats.messagesShort') }}
                            </span>
                        </div>
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
                            :class="visibleSeries[series.key]
                                ? 'cursor-pointer border-border/70 bg-background/80 hover:bg-accent/50'
                                : 'cursor-pointer border-border/40 bg-background/40 opacity-55 hover:opacity-80'"
                            @click="toggleSeries(series.key)"
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
                        <div>
                            <h3 class="text-sm font-semibold">{{ t('telegram.analytics.charts.distribution') }}</h3>
                            <span class="text-xs text-muted-foreground">{{ t('telegram.analytics.charts.distributionHint') }}</span>
                        </div>
                        <div class="group relative">
                            <span
                                class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                                :aria-label="t('telegram.analytics.help.label')"
                            >
                                ?
                            </span>
                            <div class="pointer-events-none absolute right-0 top-6 z-20 hidden w-64 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                                {{ t('telegram.analytics.help.distribution') }}
                            </div>
                        </div>
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
                    <div class="flex items-center gap-2">
                        <div class="group relative">
                            <span
                                class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                                :aria-label="t('telegram.analytics.help.label')"
                            >
                                ?
                            </span>
                            <div class="pointer-events-none absolute right-0 top-6 z-20 hidden w-64 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                                {{ t('telegram.analytics.help.opinionLeaders') }}
                            </div>
                        </div>
                        <span class="rounded-full border border-border px-2 py-1 text-xs text-muted-foreground">
                            {{ opinionLeaders.length }}
                        </span>
                    </div>
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
                                v-for="series in displayedLeaderSeries"
                                :key="series.key"
                                :d="leaderPoints(series.values)"
                                fill="none"
                                :stroke="series.color"
                                stroke-width="3"
                            />
                            <g v-for="series in displayedLeaderSeries" :key="`${series.key}-dots`">
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
                    <button
                        v-for="series in leaderSeries"
                        :key="`leader-legend-${series.key}`"
                        type="button"
                        class="cursor-pointer rounded-md border px-3 py-2 text-left text-xs transition"
                        :class="visibleLeaderSeries[series.key] === false
                            ? 'border-border/70 bg-background/40 text-muted-foreground'
                            : 'border-border/70 bg-background/70 text-foreground hover:bg-accent/60'"
                        @click="toggleLeaderSeries(series.key)"
                    >
                        <span class="inline-flex items-center gap-2 font-medium">
                            <span
                                class="inline-block h-2.5 w-2.5 rounded-full"
                                :style="{
                                    backgroundColor: series.color,
                                    opacity: visibleLeaderSeries[series.key] === false ? 0.35 : 1
                                }"
                            />
                            {{ series.label }}
                        </span>
                    </button>
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
                                    В· {{ t('telegram.analytics.stats.replies') }}: {{ formatNumber(leader.replies) }}
                                    В· {{ t('telegram.analytics.stats.reactions') }}: {{ formatNumber(leader.reactions) }}
                                    В· {{ t('telegram.analytics.stats.gifts') }}: {{ formatNumber(leader.gifts) }}
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

            <TelegramAnalyticsTopPosts :posts="payload.summary.topPosts" />
        </template>

        <TelegramAnalyticsEmptyState
            v-else
            :loading="loading"
            @refresh="loadAnalytics"
        />
    </section>
</template>
