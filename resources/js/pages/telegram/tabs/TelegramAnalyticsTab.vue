<script setup lang="ts">
import { onMounted } from 'vue';
import HelpTooltip from '@/components/ui/HelpTooltip.vue';
import {
    getRepeatQueryParams,
    isRepeatAutorunEnabled,
    readRepeatQueryInt,
    readRepeatQueryParam,
} from '@/composables/useRepeatQuery';
import { useTelegramAnalyticsTab } from '../composables/useTelegramAnalyticsTab';
import TelegramAnalyticsActivityChart from './analytics/components/TelegramAnalyticsActivityChart.vue';
import TelegramAnalyticsControlPanel from './analytics/components/TelegramAnalyticsControlPanel.vue';
import TelegramAnalyticsDistributionPanel from './analytics/components/TelegramAnalyticsDistributionPanel.vue';
import TelegramAnalyticsEmptyState from './analytics/components/TelegramAnalyticsEmptyState.vue';
import TelegramAnalyticsFraudSignals from './analytics/components/TelegramAnalyticsFraudSignals.vue';
import TelegramAnalyticsFunnelAudience from './analytics/components/TelegramAnalyticsFunnelAudience.vue';
import TelegramAnalyticsGroupProfile from './analytics/components/TelegramAnalyticsGroupProfile.vue';
import TelegramAnalyticsOpinionLeadersChart from './analytics/components/TelegramAnalyticsOpinionLeadersChart.vue';
import TelegramAnalyticsStatCards from './analytics/components/TelegramAnalyticsStatCards.vue';
import TelegramAnalyticsTopPosts from './analytics/components/TelegramAnalyticsTopPosts.vue';

const {
    t,
    PERIODS,
    PRIORITIES,
    form,
    loading,
    error,
    payload,
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
    funnelStages,
    audience,
    audienceCards,
    fraudSignals,
    opinionLeaders,
    hasOpinionLeaders,
    leaderChartWidth,
    leaderChartHeight,
    leaderChartPadding,
    leaderChartInnerWidth,
    leaderChartInnerHeight,
    leaderHoveredIndex,
    visibleLeaderSeries,
    leaderX,
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

    if (
        scorePriority === 'balanced' ||
        scorePriority === 'reach' ||
        scorePriority === 'discussion' ||
        scorePriority === 'virality'
    ) {
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
    <section class="intel-panel-strong sticky top-0 z-10 shrink-0">
        <TelegramAnalyticsControlPanel
            v-model:collapsed="analyticsPanelCollapsed"
            v-model:chat-username="form.chatUsername"
            v-model:keyword="form.keyword"
            v-model:date-from="form.dateFrom"
            v-model:date-to="form.dateTo"
            :periods="PERIODS"
            :priorities="PRIORITIES"
            :period-days="form.periodDays"
            :score-priority="form.scorePriority"
            :date-limits="dateLimits"
            :loading="loading"
            :can-load-analytics="canLoadAnalytics"
            :can-use-report-actions="canUseReportActions"
            @apply-preset="applyPreset"
            @set-priority="setPriority"
            @load="loadAnalytics"
            @open-report="openReport"
            @download-report="downloadReport"
        />

        <div
            class="mt-3 flex flex-wrap items-center gap-2 text-xs text-muted-foreground"
        >
            <span class="rounded-full border border-border px-2 py-1">{{
                periodLabel
            }}</span>
            <span
                v-if="payload"
                class="rounded-full border border-border px-2 py-1"
            >
                {{ payload.range.label }}
            </span>
            <span
                v-if="payload?.range.keyword"
                class="rounded-full border border-border px-2 py-1"
            >
                {{ t('telegram.search.keyword') }}: {{ payload.range.keyword }}
            </span>
            <span
                v-if="payload"
                class="rounded-full border border-border px-2 py-1"
            >
                {{ t('telegram.analytics.priority.title') }}:
                {{ priorityLabel(activePriority) }}
            </span>
            <span
                v-if="payload"
                class="relative flex items-center gap-1 rounded-full border border-border px-2 py-1"
            >
                <span>
                    {{ t('telegram.analytics.priority.formula') }}:
                    {{ payload.score.weights.views }}*V +
                    {{ payload.score.weights.forwards }}*F +
                    {{ payload.score.weights.replies }}*R +
                    {{ payload.score.weights.reactions }}*Re +
                    {{ payload.score.weights.gifts }}*G
                </span>
                <HelpTooltip
                    :label="t('telegram.analytics.help.label')"
                    :text="t('telegram.analytics.help.formula')"
                    width-class="w-80"
                    align="right"
                />
            </span>
        </div>

        <p v-if="error" class="mt-3 text-sm text-destructive">
            {{ error }}
        </p>
    </section>

    <section
        class="intel-scroll flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto overscroll-contain pr-1 pb-1"
    >
        <div
            v-if="loading && !payload"
            class="rounded-xl border border-sidebar-border/80 bg-card/70 p-6 text-center text-sm text-muted-foreground shadow-xl backdrop-blur"
        >
            {{ t('telegram.analytics.loading') }}
        </div>

        <template v-else-if="payload">
            <TelegramAnalyticsGroupProfile
                v-if="groupInfo"
                :group="groupInfo"
                :chat-username="payload.range.chatUsername"
            />

            <TelegramAnalyticsStatCards :cards="statCards" />

            <TelegramAnalyticsFraudSignals
                v-if="fraudSignals"
                :signals="fraudSignals"
            />

            <TelegramAnalyticsFunnelAudience
                :funnel-stages="funnelStages"
                :audience="audience"
                :audience-cards="audienceCards"
            />

            <div
                class="grid gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(340px,1fr)]"
            >
                <TelegramAnalyticsActivityChart
                    v-model:hovered-index="hoveredIndex"
                    :total-messages="totalMessages"
                    :timeline="timeline"
                    :chart-width="chartWidth"
                    :chart-height="chartHeight"
                    :padding="padding"
                    :chart-inner-width="chartInnerWidth"
                    :chart-inner-height="chartInnerHeight"
                    :hovered-bucket="hoveredBucket"
                    :hover-entries="hoverEntries"
                    :hover-card-width="hoverCardWidth"
                    :hover-card-height="hoverCardHeight"
                    :hover-card-x="hoverCardX"
                    :hover-card-y="hoverCardY"
                    :y-ticks="yTicks"
                    :trend-series="trendSeries"
                    :displayed-trend-series="displayedTrendSeries"
                    :visible-series="visibleSeries"
                    :points="points"
                    :point-dots="pointDots"
                    :hover-zone="hoverZone"
                    :x-for-index="xForIndex"
                    :toggle-series="toggleSeries"
                />

                <TelegramAnalyticsDistributionPanel
                    :top-media="payload.summary.topMedia"
                    :top-reactions="payload.summary.topReactions"
                />
            </div>

            <TelegramAnalyticsOpinionLeadersChart
                v-if="hasOpinionLeaders"
                v-model:hovered-index="leaderHoveredIndex"
                :leaders="opinionLeaders"
                :chart-width="leaderChartWidth"
                :chart-height="leaderChartHeight"
                :chart-padding="leaderChartPadding"
                :chart-inner-width="leaderChartInnerWidth"
                :chart-inner-height="leaderChartInnerHeight"
                :visible-series="visibleLeaderSeries"
                :leader-x="leaderX"
                :day-axis="leaderDayAxis"
                :series="leaderSeries"
                :displayed-series="displayedLeaderSeries"
                :hover-entries="leaderHoverEntries"
                :hover-card-width="leaderHoverCardWidth"
                :hover-card-height="leaderHoverCardHeight"
                :hover-card-x="leaderHoverCardX"
                :hover-day-label="leaderHoverDayLabel"
                :hover-x="leaderHoverX"
                :points="leaderPoints"
                :dots="leaderDots"
                :toggle-series="toggleLeaderSeries"
            />

            <TelegramAnalyticsTopPosts :posts="payload.summary.topPosts" />
        </template>

        <TelegramAnalyticsEmptyState
            v-else
            :loading="loading"
            @refresh="loadAnalytics"
        />
    </section>
</template>
