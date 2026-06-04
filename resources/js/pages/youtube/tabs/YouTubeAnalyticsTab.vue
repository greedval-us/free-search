<script setup lang="ts">
import { ExternalLink, Tags } from 'lucide-vue-next';
import { onMounted } from 'vue';
import AnalyticsControlPanel from '@/components/ui/analytics/AnalyticsControlPanel.vue';
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue';
import MetricCard from '@/components/ui/MetricCard.vue';
import SectionCard from '@/components/ui/SectionCard.vue';
import { useI18n } from '@/composables/useI18n';
import { useYouTubeAnalytics } from '../composables/useYouTubeAnalytics';
import YouTubeAnalyticsDistribution from './analytics/components/YouTubeAnalyticsDistribution.vue';
import YouTubeAnalyticsEmptyState from './analytics/components/YouTubeAnalyticsEmptyState.vue';
import YouTubeAnalyticsInsights from './analytics/components/YouTubeAnalyticsInsights.vue';
import YouTubeAnalyticsLeaders from './analytics/components/YouTubeAnalyticsLeaders.vue';
import YouTubeAnalyticsTimeline from './analytics/components/YouTubeAnalyticsTimeline.vue';

const { t, locale } = useI18n();

const {
    PERIODS,
    form,
    loading,
    error,
    result,
    panelCollapsed,
    canRun,
    canUseReportActions,
    customPeriodTooLong,
    dateLimits,
    applyPreset,
    runAnalytics,
    openReport,
    downloadReport,
    fmt,
    pct,
    formatDate,
    compactText,
    leaderGroups,
    videoMetric,
    insightLabel,
    initializeFromRepeatQuery,
} = useYouTubeAnalytics(t, locale);

onMounted(() => {
    initializeFromRepeatQuery();
});
</script>

<template>
    <AnalyticsControlPanel
        :title="t('youtube.analytics.title')"
        :help-label="t('youtube.analytics.help.label')"
        :help-text="t('youtube.analytics.hint')"
        :subtitle="t('youtube.analytics.hint')"
        :collapsed-text="t('youtube.analytics.collapsed')"
        :collapsed="panelCollapsed"
        :loading="loading"
        :can-run="canRun"
        :can-use-report-actions="canUseReportActions"
        :run-label="t('youtube.analytics.refresh')"
        :loading-label="t('youtube.common.loading')"
        :report-label="t('youtube.analytics.report')"
        :download-report-label="t('youtube.analytics.downloadReport')"
        @update:collapsed="panelCollapsed = $event"
        @run="runAnalytics"
        @open-report="openReport"
        @download-report="downloadReport"
    >
        <template #fields>
            <div class="grid gap-2.5 md:grid-cols-2 xl:grid-cols-12">
                <label class="block min-w-0 xl:col-span-2">
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                        >{{ t('youtube.analytics.mode') }}</span
                    >
                    <select
                        v-model="form.mode"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option value="channel">
                            {{ t('youtube.options.mode.channel') }}
                        </option>
                        <option value="video">
                            {{ t('youtube.options.mode.video') }}
                        </option>
                    </select>
                </label>

                <label
                    v-if="form.mode === 'video'"
                    class="block min-w-0 xl:col-span-10"
                >
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                        >{{ t('youtube.analytics.videoId') }}</span
                    >
                    <input
                        v-model="form.videoId"
                        type="text"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    />
                </label>

                <label v-else class="block min-w-0 xl:col-span-4">
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                        >{{ t('youtube.analytics.channelId') }}</span
                    >
                    <input
                        v-model="form.channelId"
                        type="text"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        :placeholder="t('youtube.analytics.channelPlaceholder')"
                    />
                </label>

                <div
                    v-if="form.mode === 'channel'"
                    class="min-w-0 xl:col-span-2"
                >
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                        >{{ t('youtube.analytics.period') }}</span
                    >
                    <div
                        class="grid h-10 grid-cols-3 gap-1 rounded-md border border-input bg-background p-1"
                    >
                        <button
                            v-for="period in PERIODS"
                            :key="period"
                            type="button"
                            class="cursor-pointer rounded-md px-2 text-xs transition"
                            :class="
                                form.periodDays === period &&
                                !form.dateFrom &&
                                !form.dateTo
                                    ? 'bg-cyan-400/15 text-cyan-200'
                                    : 'text-foreground hover:bg-accent'
                            "
                            @click="applyPreset(period)"
                        >
                            {{ period }}
                        </button>
                    </div>
                </div>

                <label
                    v-if="form.mode === 'channel'"
                    class="block min-w-0 xl:col-span-2"
                >
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                        >{{ t('youtube.analytics.dateFrom') }}</span
                    >
                    <input
                        v-model="form.dateFrom"
                        type="date"
                        :min="dateLimits.fromMin ?? undefined"
                        :max="dateLimits.fromMax ?? undefined"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    />
                </label>

                <label
                    v-if="form.mode === 'channel'"
                    class="block min-w-0 xl:col-span-2"
                >
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                        >{{ t('youtube.analytics.dateTo') }}</span
                    >
                    <input
                        v-model="form.dateTo"
                        type="date"
                        :min="dateLimits.toMin ?? undefined"
                        :max="dateLimits.toMax ?? undefined"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    />
                </label>
            </div>
        </template>
        <template #toolbarLeading>
            <p class="text-[11px] text-muted-foreground">
                {{
                    form.mode === 'channel'
                        ? t('youtube.analytics.period')
                        : t('youtube.analytics.videoId')
                }}
            </p>
        </template>
        <template #afterActions>
            <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
            <p v-else-if="customPeriodTooLong" class="text-sm text-destructive">
                {{ t('youtube.analytics.customPeriodTooLong') }}
            </p>
        </template>
    </AnalyticsControlPanel>

    <IntelResultPanel>
        <div class="intel-scroll min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
            <YouTubeAnalyticsEmptyState
                v-if="!result"
                :loading="loading"
                :disabled="!canRun"
                @refresh="runAnalytics"
            />

            <template v-else>
                <SectionCard
                    v-if="result.channel"
                    :title="t('youtube.analytics.channelProfile')"
                >
                    <div class="flex flex-col gap-4 md:flex-row">
                        <img
                            v-if="result.channel.thumbnail"
                            :src="result.channel.thumbnail"
                            :alt="result.channel.title"
                            class="h-24 w-24 rounded-md object-cover"
                            loading="lazy"
                        />
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-base font-semibold">
                                    {{ result.channel.title }}
                                </h2>
                                <a
                                    :href="result.channel.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 rounded-md border border-input px-2 py-1 text-xs text-primary hover:bg-accent"
                                >
                                    <ExternalLink class="h-3 w-3" />
                                    {{ t('youtube.common.open') }}
                                </a>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">
                                ID: {{ result.channel.id }} ·
                                {{ t('youtube.analytics.createdAt') }}:
                                {{ formatDate(result.channel.publishedAt) }} ·
                                {{ t('youtube.analytics.country') }}:
                                {{ result.channel.country || '-' }}
                            </p>
                            <p
                                class="mt-2 text-xs leading-relaxed text-muted-foreground"
                            >
                                {{
                                    compactText(
                                        result.channel.description ||
                                            result.channel.keywords ||
                                            '-'
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </SectionCard>

                <SectionCard
                    v-if="result.video"
                    :title="t('youtube.analytics.videoProfile')"
                >
                    <div class="flex flex-col gap-4 md:flex-row">
                        <img
                            v-if="result.video.thumbnail"
                            :src="result.video.thumbnail"
                            :alt="result.video.title"
                            class="h-28 w-48 rounded-md object-cover"
                            loading="lazy"
                        />
                        <div class="min-w-0 flex-1">
                            <h2 class="text-base font-semibold">
                                {{ result.video.title }}
                            </h2>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ result.video.channelTitle }} ·
                                {{ formatDate(result.video.publishedAt) }} ·
                                {{
                                    result.video.durationLabel ||
                                    result.video.duration
                                }}
                            </p>
                            <p
                                class="mt-2 text-xs leading-relaxed text-muted-foreground"
                            >
                                {{
                                    compactText(result.video.description || '-')
                                }}
                            </p>
                        </div>
                    </div>
                </SectionCard>

                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-6">
                    <MetricCard
                        :title="t('youtube.analytics.metrics.videos')"
                        :value="fmt(result.totals.videos)"
                        :help-text="t('youtube.analytics.metricHints.videos')"
                    />
                    <MetricCard
                        :title="t('youtube.analytics.metrics.views')"
                        :value="fmt(result.totals.views)"
                        :help-text="t('youtube.analytics.metricHints.views')"
                    />
                    <MetricCard
                        :title="t('youtube.analytics.metrics.likes')"
                        :value="fmt(result.totals.likes)"
                        :help-text="t('youtube.analytics.metricHints.likes')"
                    />
                    <MetricCard
                        :title="t('youtube.analytics.metrics.comments')"
                        :value="fmt(result.totals.comments)"
                        :help-text="t('youtube.analytics.metricHints.comments')"
                    />
                    <MetricCard
                        :title="t('youtube.analytics.metrics.avgViews')"
                        :value="fmt(result.totals.avgViews)"
                        :help-text="t('youtube.analytics.metricHints.avgViews')"
                    />
                    <MetricCard
                        :title="t('youtube.analytics.metrics.engagement')"
                        :value="pct(result.totals.engagementRate)"
                        :help-text="
                            t('youtube.analytics.metricHints.engagement')
                        "
                    />
                </div>

                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    <MetricCard
                        :title="t('youtube.analytics.metrics.subscribers')"
                        :value="
                            result.channel
                                ? fmt(result.channel.subscriberCount)
                                : '-'
                        "
                        :help-text="
                            t('youtube.analytics.metricHints.subscribers')
                        "
                    />
                    <MetricCard
                        :title="t('youtube.analytics.metrics.channelViews')"
                        :value="
                            result.channel ? fmt(result.channel.viewCount) : '-'
                        "
                        :help-text="
                            t('youtube.analytics.metricHints.channelViews')
                        "
                    />
                    <MetricCard
                        :title="t('youtube.analytics.metrics.medianViews')"
                        :value="fmt(result.totals.medianViews)"
                        :help-text="
                            t('youtube.analytics.metricHints.medianViews')
                        "
                    />
                    <MetricCard
                        :title="t('youtube.analytics.metrics.commentRate')"
                        :value="pct(result.totals.commentRate)"
                        :help-text="
                            t('youtube.analytics.metricHints.commentRate')
                        "
                    />
                </div>

                <YouTubeAnalyticsInsights
                    :insights="result.insights"
                    :insight-label="insightLabel"
                />

                <section class="grid gap-4 xl:grid-cols-2">
                    <YouTubeAnalyticsTimeline
                        :timeline="result.distribution.timeline"
                        :fmt="fmt"
                    />
                    <YouTubeAnalyticsDistribution
                        :distribution="result.distribution"
                    />
                </section>

                <YouTubeAnalyticsLeaders
                    :groups="leaderGroups"
                    :format-date="formatDate"
                    :video-metric="videoMetric"
                />

                <SectionCard :title="t('youtube.analytics.tags')">
                    <div
                        v-if="result.topTags.length > 0"
                        class="flex flex-wrap gap-2"
                    >
                        <span
                            v-for="tag in result.topTags"
                            :key="tag.tag"
                            class="inline-flex items-center gap-1 rounded-full border border-input px-2 py-1 text-xs"
                        >
                            <Tags class="h-3 w-3" /> {{ tag.tag }} ·
                            {{ tag.count }}
                        </span>
                    </div>
                    <p v-else class="text-xs text-muted-foreground">
                        {{ t('youtube.analytics.noTags') }}
                    </p>
                </SectionCard>
            </template>
        </div>
    </IntelResultPanel>
</template>
