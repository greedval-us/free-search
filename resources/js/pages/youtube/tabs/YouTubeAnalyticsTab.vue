<script setup lang="ts">
import { BarChart3, ChevronDown, ChevronUp, ExternalLink, LoaderCircle, Tags } from 'lucide-vue-next'
import { computed, onMounted, ref } from 'vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import HelpTooltip from '@/components/ui/HelpTooltip.vue'
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue'
import MetricCard from '@/components/ui/MetricCard.vue'
import SectionCard from '@/components/ui/SectionCard.vue'
import { getRepeatQueryParams, isRepeatAutorunEnabled, readRepeatQueryInt, readRepeatQueryParam } from '@/composables/useRepeatQuery'
import { useI18n } from '@/composables/useI18n'
import { apiRequest } from '@/lib/api'
import type { YouTubeAnalyticsPayload, YouTubeVideo } from '../types'

const { t } = useI18n()
const form = ref({ mode: 'channel' as 'video' | 'channel', videoId: '', channelId: '', limit: 25 })
const loading = ref(false)
const error = ref<string | null>(null)
const result = ref<YouTubeAnalyticsPayload | null>(null)
const panelCollapsed = ref(false)

const numberFormat = new Intl.NumberFormat()
const fmt = (value: number) => numberFormat.format(value ?? 0)
const pct = (value: number) => `${Number(value ?? 0).toFixed(2)}%`
const formatDate = (value: string) => (value ? new Date(value).toLocaleString() : '-')
const compactText = (value: string, max = 220) => value.length > max ? `${value.slice(0, max).trim()}...` : value

const clampLimit = () => {
  const value = Number(form.value.limit)
  form.value.limit = Number.isFinite(value) ? Math.min(50, Math.max(1, Math.trunc(value))) : 25
}

const canRun = computed(() => form.value.mode === 'video'
  ? form.value.videoId.trim().length > 0
  : form.value.channelId.trim().length > 0)

const timelineMax = computed(() => Math.max(...(result.value?.distribution.timeline ?? []).map((row) => row.views), 1))

const runAnalytics = async () => {
  loading.value = true
  error.value = null
  clampLimit()

  const response = await apiRequest<YouTubeAnalyticsPayload>('/youtube/analytics/summary', {
    query: {
      mode: form.value.mode,
      videoId: form.value.mode === 'video' ? form.value.videoId : '',
      channelId: form.value.mode === 'channel' ? form.value.channelId : '',
      limit: form.value.limit,
    },
  })

  loading.value = false

  if (!response.ok) {
    error.value = response.message ?? t('youtube.common.requestFailed')
    return
  }

  result.value = response.data
}

const leaderGroups = computed(() => {
  if (!result.value) {
    return []
  }

  return [
    { key: 'views', title: t('youtube.analytics.leaders.views'), items: result.value.leaders.byViews },
    { key: 'likes', title: t('youtube.analytics.leaders.likes'), items: result.value.leaders.byLikes },
    { key: 'comments', title: t('youtube.analytics.leaders.comments'), items: result.value.leaders.byComments },
    { key: 'engagement', title: t('youtube.analytics.leaders.engagement'), items: result.value.leaders.byEngagement },
  ]
})

const videoMetric = (video: YouTubeVideo, key: string) => {
  if (key === 'engagement') {
    return pct(video.engagementRate)
  }

  if (key === 'likes') {
    return fmt(video.likes)
  }

  if (key === 'comments') {
    return fmt(video.comments)
  }

  return fmt(video.views)
}

onMounted(() => {
  const params = getRepeatQueryParams()

  if (!params) {
    return
  }

  const tab = readRepeatQueryParam(params, ['tab'])
  if (tab !== 'analytics') {
    return
  }

  const mode = readRepeatQueryParam(params, ['mode'])
  const videoId = readRepeatQueryParam(params, ['videoId'])
  const channelId = readRepeatQueryParam(params, ['channelId'])
  const limit = readRepeatQueryInt(params, 'limit')

  if (mode === 'video' || mode === 'channel') form.value.mode = mode
  if (videoId !== '') form.value.videoId = videoId
  if (channelId !== '') form.value.channelId = channelId
  if (limit !== null) {
    form.value.limit = limit
    clampLimit()
  }

  if (isRepeatAutorunEnabled(params) && canRun.value) {
    void runAnalytics()
  }
})
</script>

<template>
  <section class="sticky top-0 z-10 shrink-0 intel-panel-strong">
    <div class="flex items-center justify-between gap-3">
      <div class="space-y-1">
        <div class="flex items-center gap-2 text-sm font-semibold">
          <BarChart3 class="h-4 w-4 text-cyan-400" />
          <span>{{ t('youtube.analytics.title') }}</span>
          <HelpTooltip :label="t('youtube.help.label')" :text="t('youtube.analytics.hint')" />
        </div>
        <p class="text-xs text-muted-foreground">
          {{ panelCollapsed ? t('youtube.analytics.collapsed') : t('youtube.analytics.hint') }}
        </p>
      </div>

      <button
        type="button"
        class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-input text-sm text-foreground transition hover:bg-accent"
        @click="panelCollapsed = !panelCollapsed"
      >
        <ChevronDown v-if="panelCollapsed" class="h-4 w-4" />
        <ChevronUp v-else class="h-4 w-4" />
      </button>
    </div>

    <div v-if="!panelCollapsed" class="mt-3 space-y-3">
      <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-12">
        <label class="block min-w-0 xl:col-span-2">
          <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('youtube.analytics.mode') }}</span>
          <select v-model="form.mode" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
            <option value="channel">{{ t('youtube.options.mode.channel') }}</option>
            <option value="video">{{ t('youtube.options.mode.video') }}</option>
          </select>
        </label>
        <label v-if="form.mode === 'video'" class="block min-w-0 xl:col-span-8">
          <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('youtube.analytics.videoId') }}</span>
          <input v-model="form.videoId" type="text" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
        </label>
        <label v-else class="block min-w-0 xl:col-span-8">
          <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('youtube.analytics.channelId') }}</span>
          <input v-model="form.channelId" type="text" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" :placeholder="t('youtube.analytics.channelPlaceholder')" />
        </label>
        <label class="block min-w-0 xl:col-span-2">
          <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('youtube.analytics.limit') }}</span>
          <input v-model.number="form.limit" type="number" min="1" max="50" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" @input="clampLimit" @blur="clampLimit" />
        </label>
      </div>

      <button
        :disabled="loading || !canRun"
        class="h-10 cursor-pointer rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
        @click="runAnalytics"
      >
        <LoaderCircle v-if="loading" class="mr-2 inline h-4 w-4 animate-spin" />
        {{ loading ? t('youtube.common.loading') : t('youtube.analytics.submit') }}
      </button>

      <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
    </div>
  </section>

  <IntelResultPanel>
    <div class="telegram-scroll min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
      <EmptyState v-if="!result" :text="t('youtube.common.empty')" />

      <template v-else>
        <SectionCard v-if="result.channel" :title="t('youtube.analytics.channelProfile')">
          <div class="flex flex-col gap-4 md:flex-row">
            <img v-if="result.channel.thumbnail" :src="result.channel.thumbnail" :alt="result.channel.title" class="h-24 w-24 rounded-md object-cover" loading="lazy" />
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-base font-semibold">{{ result.channel.title }}</h2>
                <a :href="result.channel.url" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-md border border-input px-2 py-1 text-xs text-primary hover:bg-accent">
                  <ExternalLink class="h-3 w-3" /> {{ t('youtube.common.open') }}
                </a>
              </div>
              <p class="mt-1 text-xs text-muted-foreground">
                ID: {{ result.channel.id }} · {{ t('youtube.analytics.createdAt') }}: {{ formatDate(result.channel.publishedAt) }} · {{ t('youtube.analytics.country') }}: {{ result.channel.country || '-' }}
              </p>
              <p class="mt-2 text-xs leading-relaxed text-muted-foreground">{{ compactText(result.channel.description || result.channel.keywords || '-') }}</p>
            </div>
          </div>
        </SectionCard>

        <SectionCard v-if="result.video" :title="t('youtube.analytics.videoProfile')">
          <div class="flex flex-col gap-4 md:flex-row">
            <img v-if="result.video.thumbnail" :src="result.video.thumbnail" :alt="result.video.title" class="h-28 w-48 rounded-md object-cover" loading="lazy" />
            <div class="min-w-0 flex-1">
              <h2 class="text-base font-semibold">{{ result.video.title }}</h2>
              <p class="mt-1 text-xs text-muted-foreground">
                {{ result.video.channelTitle }} · {{ formatDate(result.video.publishedAt) }} · {{ result.video.durationLabel || result.video.duration }}
              </p>
              <p class="mt-2 text-xs leading-relaxed text-muted-foreground">{{ compactText(result.video.description || '-') }}</p>
            </div>
          </div>
        </SectionCard>

        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-6">
          <MetricCard :title="t('youtube.analytics.metrics.videos')" :value="fmt(result.totals.videos)" />
          <MetricCard :title="t('youtube.analytics.metrics.views')" :value="fmt(result.totals.views)" />
          <MetricCard :title="t('youtube.analytics.metrics.likes')" :value="fmt(result.totals.likes)" />
          <MetricCard :title="t('youtube.analytics.metrics.comments')" :value="fmt(result.totals.comments)" />
          <MetricCard :title="t('youtube.analytics.metrics.avgViews')" :value="fmt(result.totals.avgViews)" />
          <MetricCard :title="t('youtube.analytics.metrics.engagement')" :value="pct(result.totals.engagementRate)" />
        </div>

        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
          <MetricCard :title="t('youtube.analytics.metrics.subscribers')" :value="result.channel ? fmt(result.channel.subscriberCount) : '-'" />
          <MetricCard :title="t('youtube.analytics.metrics.channelViews')" :value="result.channel ? fmt(result.channel.viewCount) : '-'" />
          <MetricCard :title="t('youtube.analytics.metrics.medianViews')" :value="fmt(result.totals.medianViews)" />
          <MetricCard :title="t('youtube.analytics.metrics.commentRate')" :value="pct(result.totals.commentRate)" />
        </div>

        <SectionCard :title="t('youtube.analytics.insights')">
          <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-4">
            <div v-for="insight in result.insights" :key="insight.key" class="rounded-lg border border-border/80 bg-background/70 p-3">
              <p class="text-xs text-muted-foreground">{{ insight.label }}</p>
              <p class="mt-1 line-clamp-2 text-sm font-semibold">{{ insight.value }}</p>
            </div>
          </div>
        </SectionCard>

        <section class="grid gap-4 xl:grid-cols-2">
          <SectionCard :title="t('youtube.analytics.timeline')">
            <div class="space-y-2">
              <div v-for="row in result.distribution.timeline" :key="row.key" class="space-y-1">
                <div class="flex items-center justify-between text-xs">
                  <span>{{ row.key }}</span>
                  <span>{{ fmt(row.views) }} {{ t('youtube.analytics.metrics.views').toLowerCase() }}</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-muted">
                  <div class="h-full rounded-full bg-cyan-400" :style="{ width: `${Math.max(4, (row.views / timelineMax) * 100)}%` }"></div>
                </div>
              </div>
            </div>
          </SectionCard>

          <SectionCard :title="t('youtube.analytics.distribution')">
            <div class="grid gap-3 md:grid-cols-3">
              <div class="rounded-lg border border-border/80 bg-background/70 p-3">
                <p class="text-xs font-medium">{{ t('youtube.analytics.duration') }}</p>
                <p class="mt-2 text-xs text-muted-foreground">Short: {{ result.distribution.duration.short }}</p>
                <p class="text-xs text-muted-foreground">Medium: {{ result.distribution.duration.medium }}</p>
                <p class="text-xs text-muted-foreground">Long: {{ result.distribution.duration.long }}</p>
              </div>
              <div class="rounded-lg border border-border/80 bg-background/70 p-3">
                <p class="text-xs font-medium">{{ t('youtube.analytics.quality') }}</p>
                <p v-for="(count, key) in result.distribution.definition" :key="key" class="mt-2 text-xs text-muted-foreground">{{ key || '-' }}: {{ count }}</p>
              </div>
              <div class="rounded-lg border border-border/80 bg-background/70 p-3">
                <p class="text-xs font-medium">{{ t('youtube.analytics.captions') }}</p>
                <p class="mt-2 text-xs text-muted-foreground">{{ t('youtube.analytics.withCaptions') }}: {{ result.distribution.captions.with }}</p>
                <p class="text-xs text-muted-foreground">{{ t('youtube.analytics.withoutCaptions') }}: {{ result.distribution.captions.without }}</p>
              </div>
            </div>
          </SectionCard>
        </section>

        <section class="grid gap-4 xl:grid-cols-2">
          <SectionCard v-for="group in leaderGroups" :key="group.key" :title="group.title">
            <div class="space-y-2">
              <article v-for="video in group.items" :key="`${group.key}-${video.id}`" class="rounded-lg border border-border/80 bg-background/70 p-3">
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <h3 class="line-clamp-2 text-sm font-medium">{{ video.title }}</h3>
                    <p class="mt-1 text-xs text-muted-foreground">
                      {{ videoMetric(video, group.key) }} · {{ video.durationLabel || video.duration }} · {{ formatDate(video.publishedAt) }}
                    </p>
                  </div>
                  <a :href="video.url" target="_blank" rel="noopener noreferrer" class="shrink-0 rounded-md border border-input px-3 py-1 text-xs hover:bg-accent">
                    {{ t('youtube.common.open') }}
                  </a>
                </div>
              </article>
            </div>
          </SectionCard>
        </section>

        <SectionCard :title="t('youtube.analytics.tags')">
          <div v-if="result.topTags.length > 0" class="flex flex-wrap gap-2">
            <span v-for="tag in result.topTags" :key="tag.tag" class="inline-flex items-center gap-1 rounded-full border border-input px-2 py-1 text-xs">
              <Tags class="h-3 w-3" /> {{ tag.tag }} · {{ tag.count }}
            </span>
          </div>
          <p v-else class="text-xs text-muted-foreground">{{ t('youtube.analytics.noTags') }}</p>
        </SectionCard>
      </template>
    </div>
  </IntelResultPanel>
</template>
