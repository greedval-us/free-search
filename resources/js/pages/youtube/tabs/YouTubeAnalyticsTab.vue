<script setup lang="ts">
import { BarChart3, ChevronDown, ChevronUp, LoaderCircle } from 'lucide-vue-next'
import { ref } from 'vue'
import HelpTooltip from '@/components/ui/HelpTooltip.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue'
import MetricCard from '@/components/ui/MetricCard.vue'
import SectionCard from '@/components/ui/SectionCard.vue'
import { useI18n } from '@/composables/useI18n'
import { apiRequest } from '@/lib/api'
import type { YouTubeAnalyticsPayload } from '../types'

const { t } = useI18n()
const form = ref({ mode: 'video' as 'video' | 'channel', videoId: '', channelId: '', limit: 10 })
const loading = ref(false)
const error = ref<string | null>(null)
const result = ref<YouTubeAnalyticsPayload | null>(null)
const panelCollapsed = ref(false)

const numberFormat = new Intl.NumberFormat()
const fmt = (value: number) => numberFormat.format(value ?? 0)

const runAnalytics = async () => {
  loading.value = true
  error.value = null

  const response = await apiRequest<YouTubeAnalyticsPayload>('/youtube/analytics/summary', {
    query: {
      mode: form.value.mode,
      videoId: form.value.videoId,
      channelId: form.value.channelId,
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
            <option value="video">{{ t('youtube.options.mode.video') }}</option>
            <option value="channel">{{ t('youtube.options.mode.channel') }}</option>
          </select>
        </label>
        <label class="block min-w-0 xl:col-span-4">
          <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('youtube.analytics.videoId') }}</span>
          <input v-model="form.videoId" type="text" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
        </label>
        <label class="block min-w-0 xl:col-span-4">
          <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('youtube.analytics.channelId') }}</span>
          <input v-model="form.channelId" type="text" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
        </label>
        <label class="block min-w-0 xl:col-span-2">
          <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('youtube.analytics.limit') }}</span>
          <input v-model.number="form.limit" type="number" min="1" max="50" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
        </label>
      </div>

      <div class="flex flex-wrap gap-2">
        <button
          :disabled="loading || (form.videoId.trim() === '' && form.channelId.trim() === '')"
          class="h-10 cursor-pointer rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
          @click="runAnalytics"
        >
          <LoaderCircle v-if="loading" class="mr-2 inline h-4 w-4 animate-spin" />
          {{ loading ? t('youtube.common.loading') : t('youtube.analytics.submit') }}
        </button>
      </div>

      <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
    </div>
  </section>

  <IntelResultPanel>
    <div class="telegram-scroll min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
      <EmptyState v-if="!result" :text="t('youtube.common.empty')" />

      <template v-else>
        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-5">
          <MetricCard :title="t('youtube.analytics.metrics.videos')" :value="fmt(result.totals.videos)" />
          <MetricCard :title="t('youtube.analytics.metrics.views')" :value="fmt(result.totals.views)" />
          <MetricCard :title="t('youtube.analytics.metrics.likes')" :value="fmt(result.totals.likes)" />
          <MetricCard :title="t('youtube.analytics.metrics.avgViews')" :value="fmt(result.totals.avgViews)" />
          <MetricCard :title="t('youtube.analytics.metrics.engagement')" :value="`${result.totals.engagementRate}%`" />
        </div>

        <SectionCard :title="t('youtube.analytics.topVideos')">
          <div class="space-y-2">
            <article v-for="video in result.topVideos" :key="video.id" class="rounded-lg border border-border/80 bg-background/70 p-3">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <h3 class="text-sm font-medium">{{ video.title }}</h3>
                  <p class="mt-1 text-xs text-muted-foreground">{{ t('youtube.analytics.metrics.views') }}: {{ fmt(video.views) }} | {{ t('youtube.analytics.metrics.likes') }}: {{ fmt(video.likes) }} | {{ t('youtube.analytics.metrics.comments') }}: {{ fmt(video.comments) }}</p>
                </div>
                <a :href="video.url" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-md border border-input px-3 py-1 text-xs hover:bg-accent">
                  {{ t('youtube.common.open') }}
                </a>
              </div>
            </article>
          </div>
        </SectionCard>
      </template>
    </div>
  </IntelResultPanel>
</template>
